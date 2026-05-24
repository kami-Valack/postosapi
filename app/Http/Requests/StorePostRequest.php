<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'admin_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'preco' => ['nullable', 'string', 'max:100'],
            'preco_premium' => ['nullable', 'string', 'max:100'],
            'combustivel' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'hours_24' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string', 'max:2048'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:100'],
        ];
    }
}
