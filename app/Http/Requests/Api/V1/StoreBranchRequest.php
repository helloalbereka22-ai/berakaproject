<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'business_id' => 'required|integer|exists:businesses,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'manager_id'  => 'nullable|integer|exists:users,id',
            'status'      => 'nullable|in:active,inactive',
        ];
    }
}
