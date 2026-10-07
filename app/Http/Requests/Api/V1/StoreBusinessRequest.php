<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:businesses,code',
            'description' => 'nullable|string|max:1000',
            'status'      => 'nullable|in:active,inactive',
        ];
    }
}
