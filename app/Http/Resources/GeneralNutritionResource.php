<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralNutritionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'icon' => $this->icon,
            'description' => $this->description,
            'foods' => FoodResource::collection($this->whenLoaded('foods')),
        ];
    }
}
