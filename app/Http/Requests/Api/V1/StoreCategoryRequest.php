<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'business_id' => 'required|integer|exists:businesses,id',
            'name'        => 'required|string|max:255',
            'type'        => 'nullable|in:product,book,raw_material,packing,general',
            'parent_id'   => 'nullable|integer|exists:categories,id',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
