<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_percent' => ['nullable', 'numeric', 'min:0.01'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'radius_meters' => ['nullable', 'integer', 'min:50'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', 'string', 'in:draft,scheduled'],
        ];
    }
}
