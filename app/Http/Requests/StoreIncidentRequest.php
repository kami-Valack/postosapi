<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('incidents.photos.max_size_kb', 5120);
        $mimes = config('incidents.photos.allowed_mimes', ['jpg', 'jpeg', 'png', 'webp']);

        return [
            'category' => ['required', 'string', Rule::in(array_keys(config('incidents.categories', [])))],
            'equipment_type' => ['required', 'string', Rule::in(array_keys(config('incidents.equipment_types', [])))],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'fuel_type_id' => ['nullable', 'integer', 'exists:fuel_types,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:'.config('incidents.photos.max_files', 5)],
            'photos.*' => ['file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
        ];
    }
}
