<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policies will be applied in controller
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0'],
            'justificativa_ajuste' => ['nullable', 'string', 'max:1000'],
            'critical_level' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
