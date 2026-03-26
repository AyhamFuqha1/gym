<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'calories' => $this->calories,
            'protein' => $this->protein,
            'carbs' => $this->carbs,
            'fat' => $this->fat,
            'badge' => $this->badge,
            'image' => $this->image,
            'serving_size' => $this->serving_size,
            'category' => new GeneralNutritionResource($this->whenLoaded('generalNutrition')),
        ];
    }
}
