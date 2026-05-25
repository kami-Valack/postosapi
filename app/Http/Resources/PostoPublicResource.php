<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Post */
class PostoPublicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $combustiveis = [];
        if ($this->relationLoaded('fuelAvailabilities')) {
            $combustiveis = $this->fuelAvailabilities
                ->sortBy(fn ($a) => $a->fuelType->sort_order ?? 0)
                ->map(fn ($a) => [
                    'tipo' => $a->fuelType->slug,
                    'nome' => $a->fuelType->name,
                    'disponibilidade' => $a->availability,
                ])
                ->values()
                ->all();
        }

        $servicesActivos = [];
        if ($this->relationLoaded('services')) {
            $servicesActivos = $this->services
                ->filter(fn ($s) => (bool) $s->pivot->is_active)
                ->pluck('name')
                ->values()
                ->all();
        }

        return [
            'id' => (string) $this->id,
            'nome' => $this->name,
            'endereco' => $this->address,
            'tipo' => $this->tipo ?? 'combustivel',
            'coordinates' => [
                'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            ],
            'preco' => $this->preco,
            'precoPremium' => $this->preco_premium,
            'combustivel' => $this->combustivel,
            'combustiveis' => $combustiveis,
            'status' => $this->status ?? ($this->is_active ? 'aberto' : 'fechado'),
            'hours24' => (bool) $this->hours_24,
            'image' => $this->image,
            'services' => $servicesActivos,
            'promocoes' => $this->when(
                isset($this->promocoes),
                fn () => $this->promocoes
            ),
        ];
    }
}
