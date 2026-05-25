<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishPriceDecreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'fuel_type_id' => ['nullable', 'integer', 'exists:fuel_types,id'],
            'preco' => ['required', 'string', 'max:100'],
            'preco_premium' => ['nullable', 'string', 'max:100'],
            'effective_from' => ['nullable', 'date'],
            'confirmation_deadline_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }
}
