<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'admin_id',
        'is_active',
        'tipo',
        'preco',
        'preco_premium',
        'combustivel',
        'status',
        'hours_24',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hours_24' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'post_service');
    }

    /**
     * @param  list<string>  $serviceNames
     */
    public function syncServiceNames(array $serviceNames): void
    {
        $ids = collect($serviceNames)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Service::query()->firstOrCreate(['name' => $name])->id)
            ->values()
            ->all();

        $this->services()->sync($ids);
    }

    public function scopePublicActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Pesquisa textual em nome, morada e tipo de combustível (postos activos).
     */
    public function scopeSearchTerm($query, string $term)
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('combustivel', 'like', $like);
        });
    }

    /**
     * Colunas necessárias para a resposta pública (menos dados = mais rápido).
     *
     * @return list<string>
     */
    public static function publicListColumns(): array
    {
        return [
            'id',
            'name',
            'address',
            'latitude',
            'longitude',
            'tipo',
            'preco',
            'preco_premium',
            'combustivel',
            'status',
            'hours_24',
            'image',
            'is_active',
        ];
    }
}
