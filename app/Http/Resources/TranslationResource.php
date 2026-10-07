<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translatable_type' => $this->translatable_type,
            'translatable_id' => $this->translatable_id,
            'language_id' => $this->language_id,
            'field' => $this->field,
            'value' => $this->value,
            'created_at' => $this->created_at,
        ];
    }
}
