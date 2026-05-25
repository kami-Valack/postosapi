<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\PostPromotion;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForPost(Post $post, ?string $status = null): Collection
    {
        $this->syncStatusesForPost($post->id);

        $query = PostPromotion::query()
            ->where('post_id', $post->id)
            ->with(['service', 'product'])
            ->orderByDesc('starts_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn (PostPromotion $p) => $this->format($p));
    }

    /**
     * Promoções activas para API pública (cache 60s).
     *
     * @return list<array<string, mixed>>
     */
    public function activeForPublic(Post $post): array
    {
        return Cache::remember(
            'post_promotions_active_'.$post->id,
            60,
            function () use ($post) {
                $this->syncStatusesForPost($post->id);

                return PostPromotion::query()
                    ->where('post_id', $post->id)
                    ->currentlyActive()
                    ->with(['service', 'product'])
                    ->get()
                    ->map(fn (PostPromotion $p) => $this->formatPublic($p))
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Post $post, User $user, array $data): PostPromotion
    {
        $this->validateTarget($post, $data);
        $startsAt = \Carbon\Carbon::parse($data['starts_at']);
        $endsAt = \Carbon\Carbon::parse($data['ends_at']);
        $this->validateDates($startsAt, $endsAt);
        $this->validateDiscount((float) $data['discount_percent']);

        $status = $startsAt->isFuture() ? 'scheduled' : ($endsAt->isPast() ? 'ended' : 'active');

        $promotion = PostPromotion::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'service_id' => $data['service_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
        ]);

        Cache::forget('post_promotions_active_'.$post->id);

        AuditLog::record(
            $user->id,
            'gestor.promotion.create',
            PostPromotion::class,
            $promotion->id,
            null,
            $promotion->toArray()
        );

        return $promotion->load(['service', 'product']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PostPromotion $promotion, User $user, array $data): PostPromotion
    {
        if (in_array($promotion->status, ['ended', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Não é possível alterar uma promoção terminada ou cancelada.'],
            ]);
        }

        $before = $promotion->toArray();

        if (isset($data['title'])) {
            $promotion->title = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $promotion->description = $data['description'];
        }
        if (isset($data['discount_percent'])) {
            $this->validateDiscount((float) $data['discount_percent']);
            $promotion->discount_percent = $data['discount_percent'];
        }
        if (isset($data['starts_at'], $data['ends_at'])) {
            $startsAt = \Carbon\Carbon::parse($data['starts_at']);
            $endsAt = \Carbon\Carbon::parse($data['ends_at']);
            $this->validateDates($startsAt, $endsAt);
            $promotion->starts_at = $startsAt;
            $promotion->ends_at = $endsAt;
        }

        $promotion->save();
        $this->syncStatusesForPost($promotion->post_id);
        Cache::forget('post_promotions_active_'.$promotion->post_id);

        AuditLog::record(
            $user->id,
            'gestor.promotion.update',
            PostPromotion::class,
            $promotion->id,
            $before,
            $promotion->fresh()->toArray()
        );

        return $promotion->load(['service', 'product']);
    }

    public function cancel(PostPromotion $promotion, User $user): PostPromotion
    {
        if ($promotion->status === 'cancelled') {
            return $promotion;
        }

        $before = $promotion->toArray();
        $promotion->status = 'cancelled';
        $promotion->save();

        Cache::forget('post_promotions_active_'.$promotion->post_id);

        AuditLog::record(
            $user->id,
            'gestor.promotion.cancel',
            PostPromotion::class,
            $promotion->id,
            $before,
            $promotion->toArray()
        );

        return $promotion;
    }

    public function syncStatusesForPost(int $postId): void
    {
        $now = now();

        PostPromotion::query()
            ->where('post_id', $postId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->update(['status' => 'active']);

        PostPromotion::query()
            ->where('post_id', $postId)
            ->whereIn('status', ['scheduled', 'active'])
            ->where('ends_at', '<', $now)
            ->update(['status' => 'ended']);
    }

    public function syncAllStatuses(): int
    {
        $now = now();
        $activated = PostPromotion::query()
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->update(['status' => 'active']);

        $ended = PostPromotion::query()
            ->whereIn('status', ['scheduled', 'active'])
            ->where('ends_at', '<', $now)
            ->update(['status' => 'ended']);

        return $activated + $ended;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateTarget(Post $post, array $data): void
    {
        $hasService = ! empty($data['service_id']);
        $hasProduct = ! empty($data['product_id']);

        if ($hasService === $hasProduct) {
            throw ValidationException::withMessages([
                'target' => ['Indique exactamente um alvo: service_id ou product_id (não combustível).'],
            ]);
        }

        if ($hasService) {
            $linked = $post->services()->where('services.id', $data['service_id'])->exists();
            if (! $linked) {
                throw ValidationException::withMessages([
                    'service_id' => ['O serviço não está associado a este posto.'],
                ]);
            }
        }

        if ($hasProduct) {
            $product = Product::query()->find($data['product_id']);
            if (! $product || $product->is_combustible) {
                throw ValidationException::withMessages([
                    'product_id' => ['Promoções não se aplicam a combustíveis ou energia.'],
                ]);
            }
            $hasStock = $post->stocks()->where('product_id', $product->id)->exists();
            if (! $hasStock) {
                throw ValidationException::withMessages([
                    'product_id' => ['O produto não tem stock registado neste posto.'],
                ]);
            }
        }
    }

    private function validateDiscount(float $percent): void
    {
        $max = (float) config('promotions.max_discount_percent', 25);
        if ($percent <= 0 || $percent > $max) {
            throw ValidationException::withMessages([
                'discount_percent' => ["O desconto deve ser entre 0,01% e {$max}% (limite admin)."],
            ]);
        }
    }

    private function validateDates(\Carbon\Carbon $startsAt, \Carbon\Carbon $endsAt): void
    {
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['A data de fim deve ser posterior à de início.'],
            ]);
        }

        $minHours = (int) config('promotions.min_duration_hours', 1);
        $maxDays = (int) config('promotions.max_duration_days', 30);

        if ($startsAt->diffInHours($endsAt) < $minHours) {
            throw ValidationException::withMessages([
                'ends_at' => ["A promoção deve durar pelo menos {$minHours} hora(s)."],
            ]);
        }

        if ($startsAt->diffInDays($endsAt) > $maxDays) {
            throw ValidationException::withMessages([
                'ends_at' => ["A promoção não pode exceder {$maxDays} dias."],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PostPromotion $promotion): array
    {
        return [
            'id' => $promotion->id,
            'post_id' => $promotion->post_id,
            'title' => $promotion->title,
            'description' => $promotion->description,
            'discount_percent' => (float) $promotion->discount_percent,
            'starts_at' => $promotion->starts_at?->toIso8601String(),
            'ends_at' => $promotion->ends_at?->toIso8601String(),
            'status' => $promotion->status,
            'service' => $promotion->service ? [
                'id' => $promotion->service->id,
                'name' => $promotion->service->name,
            ] : null,
            'product' => $promotion->product ? [
                'id' => $promotion->product->id,
                'name' => $promotion->product->name,
            ] : null,
            'created_at' => $promotion->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPublic(PostPromotion $promotion): array
    {
        $base = $this->format($promotion);
        unset($base['post_id'], $base['created_at']);

        return $base;
    }
}
