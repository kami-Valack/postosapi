<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\PostPriceConfirmation;
use App\Models\PriceDecree;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PriceDecreeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function publish(array $data, User $publisher): PriceDecree
    {
        $effectiveFrom = isset($data['effective_from'])
            ? \Carbon\Carbon::parse($data['effective_from'])
            : now();

        $deadlineHours = (int) ($data['confirmation_deadline_hours']
            ?? config('prices.confirmation_deadline_hours', 48));

        $decree = PriceDecree::query()->create([
            'reference' => $data['reference'] ?? null,
            'fuel_type_id' => $data['fuel_type_id'] ?? null,
            'preco' => $data['preco'],
            'preco_premium' => $data['preco_premium'] ?? null,
            'effective_from' => $effectiveFrom,
            'confirmation_deadline' => $effectiveFrom->copy()->addHours($deadlineHours),
            'published_by' => $publisher->id,
        ]);

        AuditLog::record(
            $publisher->id,
            'admin.price_decree.publish',
            PriceDecree::class,
            $decree->id,
            null,
            $decree->toArray()
        );

        return $decree->load('fuelType');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForPost(Post $post): Collection
    {
        $decrees = PriceDecree::query()
            ->with(['fuelType', 'confirmations' => fn ($q) => $q->where('post_id', $post->id)])
            ->orderByDesc('effective_from')
            ->get();

        return $decrees->map(fn (PriceDecree $decree) => $this->formatDecreeForPost($decree, $post));
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(Post $post, PriceDecree $decree, User $gestor, ?string $motivoAtraso): array
    {
        if (PostPriceConfirmation::query()
            ->where('post_id', $post->id)
            ->where('price_decree_id', $decree->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'price_decree' => ['Este decreto já foi confirmado para este posto.'],
            ]);
        }

        $wasLate = $decree->isPastDeadline();

        if ($wasLate && empty($motivoAtraso)) {
            throw ValidationException::withMessages([
                'motivo_atraso' => ['Indique o motivo do atraso na confirmação (RN-G-001).'],
            ]);
        }

        $before = ['preco' => $post->preco, 'preco_premium' => $post->preco_premium];

        $confirmation = PostPriceConfirmation::query()->create([
            'post_id' => $post->id,
            'price_decree_id' => $decree->id,
            'user_id' => $gestor->id,
            'confirmed_at' => now(),
            'motivo_atraso' => $wasLate ? $motivoAtraso : null,
            'was_late' => $wasLate,
        ]);

        $post->preco = $decree->preco;
        if ($decree->preco_premium !== null) {
            $post->preco_premium = $decree->preco_premium;
        }
        $post->save();

        PostoSearchService::flushCache();

        AuditLog::record(
            $gestor->id,
            'gestor.price_decree.confirm',
            Post::class,
            $post->id,
            $before,
            [
                'price_decree_id' => $decree->id,
                'preco' => $post->preco,
                'preco_premium' => $post->preco_premium,
                'was_late' => $wasLate,
                'motivo_atraso' => $motivoAtraso,
            ]
        );

        return [
            'confirmation' => $confirmation->load('user'),
            'post' => [
                'id' => $post->id,
                'preco' => $post->preco,
                'precoPremium' => $post->preco_premium,
            ],
            'decree' => $this->formatDecreeForPost($decree->fresh(['fuelType', 'confirmations']), $post),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDecreeForPost(PriceDecree $decree, Post $post): array
    {
        $confirmation = $decree->confirmations->first();

        $pending = $confirmation === null;
        $late = $pending && $decree->isPastDeadline();

        return [
            'id' => $decree->id,
            'reference' => $decree->reference,
            'combustivel' => $decree->fuelType?->name,
            'combustivel_slug' => $decree->fuelType?->slug,
            'preco' => $decree->preco,
            'preco_premium' => $decree->preco_premium,
            'effective_from' => $decree->effective_from?->toIso8601String(),
            'confirmation_deadline' => $decree->confirmation_deadline?->toIso8601String(),
            'status' => $confirmation ? 'confirmado' : ($late ? 'pendente_atrasado' : 'pendente'),
            'confirmed_at' => $confirmation?->confirmed_at?->toIso8601String(),
            'motivo_atraso' => $confirmation?->motivo_atraso,
            'was_late' => $confirmation?->was_late,
            'requer_motivo_atraso' => $pending && $late,
        ];
    }
}
