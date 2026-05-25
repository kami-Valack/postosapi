<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FuelAvailabilityHistory;
use App\Models\FuelType;
use App\Models\Post;
use App\Models\PostFuelAvailability;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestorPostOperationalService
{
    /**
     * @return array<string, mixed>
     */
    public function show(Post $post): array
    {
        $post->load([
            'services' => fn ($q) => $q->orderBy('name'),
            'fuelAvailabilities.fuelType',
        ]);

        return $this->formatOperationalPayload($post);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Post $post, User $user, array $data): array
    {
        return DB::transaction(function () use ($post, $user, $data) {
            $before = $this->show($post);

            if (array_key_exists('status', $data)) {
                $post->status = $data['status'];
            }
            if (array_key_exists('hours_24', $data)) {
                $post->hours_24 = (bool) $data['hours_24'];
            }
            $post->save();

            if (isset($data['services']) && is_array($data['services'])) {
                $this->syncServices($post, $data['services']);
            }

            if (isset($data['combustiveis']) && is_array($data['combustiveis'])) {
                $this->syncFuelAvailabilities($post, $user, $data['combustiveis']);
            }

            PostoSearchService::flushCache();

            $post->refresh()->load([
                'services' => fn ($q) => $q->orderBy('name'),
                'fuelAvailabilities.fuelType',
            ]);

            $after = $this->formatOperationalPayload($post);

            AuditLog::record(
                $user->id,
                'gestor.post.operational.update',
                Post::class,
                $post->id,
                $before,
                $after
            );

            return $after;
        });
    }

    /**
     * @param  list<array{name: string, active?: bool, motivo_desativacao?: string|null}>  $services
     */
    private function syncServices(Post $post, array $services): void
    {
        $sync = [];

        foreach ($services as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $service = Service::query()->where('name', $name)->first();
            if (! $service) {
                throw ValidationException::withMessages([
                    'services' => ["Serviço não aprovado no catálogo: {$name}"],
                ]);
            }

            $active = array_key_exists('active', $item) ? (bool) $item['active'] : true;
            $motivo = $item['motivo_desativacao'] ?? null;

            if (! $active && empty($motivo)) {
                throw ValidationException::withMessages([
                    'services' => ["Indique motivo_desativacao para o serviço: {$name}"],
                ]);
            }

            $sync[$service->id] = [
                'is_active' => $active,
                'motivo_desativacao' => $active ? null : $motivo,
            ];
        }

        $post->services()->sync($sync);
    }

    /**
     * @param  list<array{slug?: string, tipo?: string, disponibilidade: string, motivo_fora_stock?: string|null}>  $combustiveis
     */
    private function syncFuelAvailabilities(Post $post, User $user, array $combustiveis): void
    {
        foreach ($combustiveis as $item) {
            $slug = $item['slug'] ?? $item['tipo'] ?? null;
            if (! $slug) {
                continue;
            }

            $fuelType = FuelType::query()->where('slug', strtolower((string) $slug))->first();
            if (! $fuelType) {
                throw ValidationException::withMessages([
                    'combustiveis' => ["Tipo de combustível inválido: {$slug}"],
                ]);
            }

            $availability = $item['disponibilidade'] ?? $item['availability'] ?? 'em_stock';
            if (! in_array($availability, ['em_stock', 'fora_stock'], true)) {
                throw ValidationException::withMessages([
                    'combustiveis' => ["Disponibilidade inválida para {$slug}"],
                ]);
            }

            $motivo = $item['motivo_fora_stock'] ?? null;
            if ($availability === 'fora_stock' && empty($motivo)) {
                throw ValidationException::withMessages([
                    'combustiveis' => ["Indique motivo_fora_stock para {$slug}"],
                ]);
            }

            $record = PostFuelAvailability::query()->firstOrNew([
                'post_id' => $post->id,
                'fuel_type_id' => $fuelType->id,
            ]);

            $old = $record->exists ? $record->availability : null;

            $record->availability = $availability;
            $record->motivo_fora_stock = $availability === 'fora_stock' ? $motivo : null;
            $record->updated_by = $user->id;
            $record->save();

            if ($old !== null && $old !== $availability) {
                FuelAvailabilityHistory::query()->create([
                    'post_fuel_availability_id' => $record->id,
                    'old_availability' => $old,
                    'new_availability' => $availability,
                    'motivo_fora_stock' => $record->motivo_fora_stock,
                    'user_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function formatOperationalPayload(Post $post): array
    {
        $activeServices = [];
        $inactiveServices = [];

        foreach ($post->services as $service) {
            $entry = [
                'name' => $service->name,
                'active' => (bool) $service->pivot->is_active,
                'motivo_desativacao' => $service->pivot->motivo_desativacao,
            ];
            if ($service->pivot->is_active) {
                $activeServices[] = $service->name;
            } else {
                $inactiveServices[] = $entry;
            }
        }

        $combustiveis = $post->fuelAvailabilities
            ->sortBy(fn ($a) => $a->fuelType->sort_order ?? 0)
            ->map(fn (PostFuelAvailability $a) => [
                'slug' => $a->fuelType->slug,
                'nome' => $a->fuelType->name,
                'disponibilidade' => $a->availability,
                'motivo_fora_stock' => $a->motivo_fora_stock,
            ])
            ->values()
            ->all();

        return [
            'post_id' => $post->id,
            'nome' => $post->name,
            'status' => $post->status ?? 'aberto',
            'hours24' => (bool) $post->hours_24,
            'services' => [
                'activos' => $activeServices,
                'detalhe' => $post->services->map(fn ($s) => [
                    'name' => $s->name,
                    'active' => (bool) $s->pivot->is_active,
                    'motivo_desativacao' => $s->pivot->motivo_desativacao,
                ])->values()->all(),
            ],
            'combustiveis' => $combustiveis,
        ];
    }

    /**
     * Inicializa combustíveis em stock para um posto novo.
     */
    public function ensureDefaultFuelAvailabilities(Post $post, ?int $userId = null): void
    {
        foreach (FuelType::query()->orderBy('sort_order')->get() as $fuelType) {
            PostFuelAvailability::query()->firstOrCreate(
                ['post_id' => $post->id, 'fuel_type_id' => $fuelType->id],
                [
                    'availability' => 'em_stock',
                    'updated_by' => $userId,
                ]
            );
        }
    }
}
