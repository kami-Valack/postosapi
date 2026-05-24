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
            'status' => $this->status ?? ($this->is_active ? 'aberto' : 'fechado'),
            'hours24' => (bool) $this->hours_24,
            'image' => $this->image,
            'services' => $this->relationLoaded('services')
                ? $this->services->pluck('name')->values()->all()
                : [],
        ];
    }
}
