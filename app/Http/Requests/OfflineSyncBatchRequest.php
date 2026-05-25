<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfflineSyncBatchRequest extends FormRequest
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
            'device_id' => ['nullable', 'string', 'max:128'],
            'client_batch_at' => ['nullable', 'date'],
            'operations' => ['required', 'array', 'min:1'],
            'operations.*.id' => ['required', 'uuid'],
            'operations.*.type' => ['required', 'string'],
            'operations.*.payload' => ['required', 'array'],
            'operations.*.client_timestamp' => ['required', 'date'],
        ];
    }
}
