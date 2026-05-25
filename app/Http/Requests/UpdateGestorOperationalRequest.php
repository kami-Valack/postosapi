<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGestorOperationalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(config('fuel_types.post_status', ['aberto', 'fechado', 'manutencao']))],
            'hours_24' => ['sometimes', 'boolean'],
            'services' => ['sometimes', 'array'],
            'services.*.name' => ['required_with:services', 'string', 'max:100'],
            'services.*.active' => ['sometimes', 'boolean'],
            'services.*.motivo_desativacao' => ['nullable', 'string', 'max:1000'],
            'combustiveis' => ['sometimes', 'array'],
            'combustiveis.*.slug' => ['sometimes', 'string', 'max:50'],
            'combustiveis.*.tipo' => ['sometimes', 'string', 'max:50'],
            'combustiveis.*.disponibilidade' => ['required_with:combustiveis', 'string', Rule::in(['em_stock', 'fora_stock'])],
            'combustiveis.*.motivo_fora_stock' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
