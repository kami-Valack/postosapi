<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInteraction extends Model
{
    protected $fillable = [
        'post_campaign_id',
        'event_type',
        'client_user_id',
        'latitude',
        'longitude',
        'distance_meters',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PostCampaign::class, 'post_campaign_id');
    }
}
