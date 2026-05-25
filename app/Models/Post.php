<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsToMany(Service::class, 'post_service')
            ->withPivot(['is_active', 'motivo_desativacao'])
            ->withTimestamps();
    }

    public function fuelAvailabilities(): HasMany
    {
        return $this->hasMany(PostFuelAvailability::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(PostPromotion::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(PostCampaign::class);
    }

    /**
     * @param  list<string>  $serviceNames
     */
    public function syncServiceNames(array $serviceNames): void
    {
        $sync = collect($serviceNames)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->mapWithKeys(fn (string $name) => [
                Service::query()->firstOrCreate(['name' => $name])->id => [
                    'is_active' => true,
                    'motivo_desativacao' => null,
                ],
            ])
            ->all();

        $this->services()->sync($sync);
    }

    public function scopePublicActive($query)
    {
        return $query->where('is_active', true);
    }

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
