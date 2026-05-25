<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackCampaignInteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'in:view,click,conversion'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'client_user_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
