<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IncidentPhoto extends Model
{
    protected $fillable = [
        'incident_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }
}
