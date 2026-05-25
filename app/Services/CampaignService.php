<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CampaignInteraction;
use App\Models\Post;
use App\Models\PostCampaign;
use App\Models\Product;
use App\Models\User;
use App\Support\GeoDistance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForPost(Post $post, ?string $status = null): Collection
    {
        $this->syncStatusesForPost($post->id);

        $query = PostCampaign::query()
            ->where('post_id', $post->id)
            ->with(['service', 'product'])
            ->orderByDesc('starts_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn (PostCampaign $c) => $this->format($c));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Post $post, User $user, array $data): PostCampaign
    {
        $this->validateTarget($post, $data);
        $startsAt = \Carbon\Carbon::parse($data['starts_at']);
        $endsAt = \Carbon\Carbon::parse($data['ends_at']);
        $this->validateDates($startsAt, $endsAt);
        if (isset($data['discount_percent'])) {
            $this->validateDiscount((float) $data['discount_percent']);
        }

        $radius = (int) ($data['radius_meters'] ?? config('campaigns.default_radius_meters', 500));
        $this->validateRadius($radius);

        $status = $data['status'] ?? 'draft';
        if (! in_array($status, ['draft', 'scheduled'], true)) {
            $status = 'scheduled';
        }
        if ($status === 'scheduled' && $startsAt->isPast() && $endsAt->isFuture()) {
            $status = 'active';
        }

        $campaign = PostCampaign::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'service_id' => $data['service_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'budget_amount' => $data['budget_amount'] ?? null,
            'radius_meters' => $radius,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
        ]);

        Cache::forget('post_campaigns_active_'.$post->id);

        AuditLog::record($user->id, 'gestor.campaign.create', PostCampaign::class, $campaign->id, null, $campaign->toArray());

        return $campaign->load(['service', 'product']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PostCampaign $campaign, User $user, array $data): PostCampaign
    {
        if (in_array($campaign->status, ['ended', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => ['Campanha terminada ou cancelada.']]);
        }

        $before = $campaign->toArray();

        foreach (['title', 'description', 'budget_amount'] as $field) {
            if (array_key_exists($field, $data)) {
                $campaign->{$field} = $data[$field];
            }
        }
        if (isset($data['discount_percent'])) {
            $this->validateDiscount((float) $data['discount_percent']);
            $campaign->discount_percent = $data['discount_percent'];
        }
        if (isset($data['radius_meters'])) {
            $this->validateRadius((int) $data['radius_meters']);
            $campaign->radius_meters = (int) $data['radius_meters'];
        }
        if (isset($data['starts_at'], $data['ends_at'])) {
            $startsAt = \Carbon\Carbon::parse($data['starts_at']);
            $endsAt = \Carbon\Carbon::parse($data['ends_at']);
            $this->validateDates($startsAt, $endsAt);
            $campaign->starts_at = $startsAt;
            $campaign->ends_at = $endsAt;
        }

        $campaign->save();
        $this->syncStatusesForPost($campaign->post_id);
        Cache::forget('post_campaigns_active_'.$campaign->post_id);

        AuditLog::record($user->id, 'gestor.campaign.update', PostCampaign::class, $campaign->id, $before, $campaign->fresh()->toArray());

        return $campaign->load(['service', 'product']);
    }

    public function pause(PostCampaign $campaign, User $user): PostCampaign
    {
        if ($campaign->status !== 'active') {
            throw ValidationException::withMessages(['status' => ['Só campanhas activas podem ser pausadas.']]);
        }
        $campaign->status = 'paused';
        $campaign->save();
        Cache::forget('post_campaigns_active_'.$campaign->post_id);
        AuditLog::record($user->id, 'gestor.campaign.pause', PostCampaign::class, $campaign->id, null, ['status' => 'paused']);

        return $campaign;
    }

    public function resume(PostCampaign $campaign, User $user): PostCampaign
    {
        if ($campaign->status !== 'paused') {
            throw ValidationException::withMessages(['status' => ['Só campanhas pausadas podem ser retomadas.']]);
        }
        if ($campaign->ends_at->isPast()) {
            throw ValidationException::withMessages(['ends_at' => ['A campanha já expirou.']]);
        }
        $campaign->status = 'active';
        $campaign->save();
        Cache::forget('post_campaigns_active_'.$campaign->post_id);
        AuditLog::record($user->id, 'gestor.campaign.resume', PostCampaign::class, $campaign->id, null, ['status' => 'active']);

        return $campaign;
    }

    public function cancel(PostCampaign $campaign, User $user): PostCampaign
    {
        $campaign->status = 'cancelled';
        $campaign->save();
        Cache::forget('post_campaigns_active_'.$campaign->post_id);
        AuditLog::record($user->id, 'gestor.campaign.cancel', PostCampaign::class, $campaign->id, null, ['status' => 'cancelled']);

        return $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitFeedback(PostCampaign $campaign, User $user, array $data): PostCampaign
    {
        $campaign->feedback_qualitativo = $data['feedback_qualitativo'];
        $campaign->feedback_submitted_at = now();
        $campaign->save();

        AuditLog::record($user->id, 'gestor.campaign.feedback', PostCampaign::class, $campaign->id, null, [
            'feedback_qualitativo' => $campaign->feedback_qualitativo,
        ]);

        return $campaign;
    }

    /**
     * @return array<string, mixed>
     */
    public function performance(PostCampaign $campaign): array
    {
        $views = (int) $campaign->views_count;
        $clicks = (int) $campaign->clicks_count;
        $conversions = (int) $campaign->conversions_count;

        $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : null;
        $conversionRate = $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : null;

        $budget = $campaign->budget_amount !== null ? (float) $campaign->budget_amount : null;
        $spent = (float) $campaign->spent_amount;
        $roi = null;
        if ($budget !== null && $budget > 0 && $conversions > 0) {
            $estimatedRevenue = $conversions * ($campaign->discount_percent ?? 10) * 100;
            $roi = round((($estimatedRevenue - $spent) / max($spent, 1)) * 100, 2);
        }

        return [
            'campaign_id' => $campaign->id,
            'status' => $campaign->status,
            'metrics' => [
                'views' => $views,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'ctr_percent' => $ctr,
                'conversion_rate_percent' => $conversionRate,
                'roi_percent' => $roi,
            ],
            'budget' => [
                'amount' => $budget,
                'spent' => $spent,
                'remaining' => $budget !== null ? max(0, $budget - $spent) : null,
            ],
            'feedback_qualitativo' => $campaign->feedback_qualitativo,
            'feedback_submitted_at' => $campaign->feedback_submitted_at?->toIso8601String(),
            'period' => [
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Campanhas activas num posto (cache).
     *
     * @return list<array<string, mixed>>
     */
    public function activeForPost(Post $post): array
    {
        return Cache::remember('post_campaigns_active_'.$post->id, 60, function () use ($post) {
            $this->syncStatusesForPost($post->id);

            return PostCampaign::query()
                ->where('post_id', $post->id)
                ->currentlyActive()
                ->with(['service', 'product'])
                ->get()
                ->map(fn (PostCampaign $c) => $this->formatPublic($c))
                ->values()
                ->all();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function nearby(float $latitude, float $longitude, ?int $postId = null, int $limit = 20): array
    {
        $query = Post::query()
            ->publicActive()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($postId) {
            $query->where('id', $postId);
        }

        $results = [];
        foreach ($query->get() as $post) {
            $distance = GeoDistance::metersBetween(
                $latitude,
                $longitude,
                (float) $post->latitude,
                (float) $post->longitude
            );

            $campaigns = PostCampaign::query()
                ->where('post_id', $post->id)
                ->currentlyActive()
                ->with(['service', 'product'])
                ->get()
                ->filter(fn (PostCampaign $c) => $distance <= $c->radius_meters);

            foreach ($campaigns as $campaign) {
                $results[] = array_merge($this->formatPublic($campaign), [
                    'post_id' => $post->id,
                    'post_name' => $post->name,
                    'distance_meters' => $distance,
                ]);
            }
        }

        usort($results, fn ($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);

        return array_slice($results, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function trackInteraction(PostCampaign $campaign, array $data): array
    {
        $eventType = $data['event_type'];
        if (! in_array($eventType, config('campaigns.interaction_types', []), true)) {
            throw ValidationException::withMessages(['event_type' => ['Tipo de evento inválido.']]);
        }

        if ($campaign->status !== 'active' || $campaign->ends_at->isPast()) {
            throw ValidationException::withMessages(['campaign' => ['Campanha não está activa.']]);
        }

        $campaign->load('post');
        $post = $campaign->post;
        if (! $post->latitude || ! $post->longitude) {
            throw ValidationException::withMessages(['post' => ['Posto sem coordenadas para validação geográfica.']]);
        }

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];
        $distance = GeoDistance::metersBetween($lat, $lng, (float) $post->latitude, (float) $post->longitude);

        if ($distance > $campaign->radius_meters) {
            throw ValidationException::withMessages([
                'proximity' => ["Utilizador fora do raio da campanha ({$distance}m > {$campaign->radius_meters}m)."],
            ]);
        }

        return DB::transaction(function () use ($campaign, $data, $eventType, $lat, $lng, $distance) {
            CampaignInteraction::query()->create([
                'post_campaign_id' => $campaign->id,
                'event_type' => $eventType,
                'client_user_id' => $data['client_user_id'] ?? null,
                'latitude' => $lat,
                'longitude' => $lng,
                'distance_meters' => $distance,
            ]);

            $column = match ($eventType) {
                'view' => 'views_count',
                'click' => 'clicks_count',
                'conversion' => 'conversions_count',
            };
            $campaign->increment($column);

            if ($eventType === 'conversion' && $campaign->budget_amount !== null) {
                $cost = (float) env('CAMPAIGN_COST_PER_CONVERSION', 1);
                $campaign->increment('spent_amount', $cost);
            }

            return [
                'campaign_id' => $campaign->id,
                'event_type' => $eventType,
                'distance_meters' => $distance,
                'recorded' => true,
            ];
        });
    }

    public function syncStatusesForPost(int $postId): void
    {
        $now = now();
        PostCampaign::query()
            ->where('post_id', $postId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->update(['status' => 'active']);

        PostCampaign::query()
            ->where('post_id', $postId)
            ->whereIn('status', ['scheduled', 'active', 'paused'])
            ->where('ends_at', '<', $now)
            ->update(['status' => 'ended']);
    }

    public function syncAllStatuses(): int
    {
        $now = now();
        $a = PostCampaign::query()
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->update(['status' => 'active']);

        $b = PostCampaign::query()
            ->whereIn('status', ['scheduled', 'active', 'paused'])
            ->where('ends_at', '<', $now)
            ->update(['status' => 'ended']);

        return $a + $b;
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
                'target' => ['Indique service_id ou product_id (não combustível).'],
            ]);
        }
        if ($hasService && ! $post->services()->where('services.id', $data['service_id'])->exists()) {
            throw ValidationException::withMessages(['service_id' => ['Serviço não associado ao posto.']]);
        }
        if ($hasProduct) {
            $product = Product::query()->find($data['product_id']);
            if (! $product || $product->is_combustible) {
                throw ValidationException::withMessages(['product_id' => ['Não aplicável a combustíveis/energia.']]);
            }
        }
    }

    private function validateDiscount(float $percent): void
    {
        $max = (float) config('campaigns.max_discount_percent', 30);
        if ($percent <= 0 || $percent > $max) {
            throw ValidationException::withMessages([
                'discount_percent' => ["Desconto entre 0,01% e {$max}%."],
            ]);
        }
    }

    private function validateRadius(int $radius): void
    {
        $max = (int) config('campaigns.max_radius_meters', 5000);
        if ($radius < 50 || $radius > $max) {
            throw ValidationException::withMessages([
                'radius_meters' => ["Raio entre 50m e {$max}m."],
            ]);
        }
    }

    private function validateDates(\Carbon\Carbon $startsAt, \Carbon\Carbon $endsAt): void
    {
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => ['Fim deve ser posterior ao início.']]);
        }
        $maxDays = (int) config('campaigns.max_duration_days', 90);
        if ($startsAt->diffInDays($endsAt) > $maxDays) {
            throw ValidationException::withMessages(['ends_at' => ["Duração máxima: {$maxDays} dias."]]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function format(PostCampaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'post_id' => $campaign->post_id,
            'title' => $campaign->title,
            'description' => $campaign->description,
            'discount_percent' => $campaign->discount_percent !== null ? (float) $campaign->discount_percent : null,
            'budget_amount' => $campaign->budget_amount !== null ? (float) $campaign->budget_amount : null,
            'spent_amount' => (float) $campaign->spent_amount,
            'radius_meters' => $campaign->radius_meters,
            'starts_at' => $campaign->starts_at?->toIso8601String(),
            'ends_at' => $campaign->ends_at?->toIso8601String(),
            'status' => $campaign->status,
            'service' => $campaign->service ? ['id' => $campaign->service->id, 'name' => $campaign->service->name] : null,
            'product' => $campaign->product ? ['id' => $campaign->product->id, 'name' => $campaign->product->name] : null,
            'metrics' => [
                'views' => (int) $campaign->views_count,
                'clicks' => (int) $campaign->clicks_count,
                'conversions' => (int) $campaign->conversions_count,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPublic(PostCampaign $campaign): array
    {
        $base = $this->format($campaign);
        unset($base['post_id'], $base['spent_amount'], $base['budget_amount']);

        return $base;
    }
}
