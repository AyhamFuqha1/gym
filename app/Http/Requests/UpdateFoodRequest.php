<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'general_nutrition_id' => 'sometimes|exists:general_nutrition,id',
            'name' => 'sometimes|string|max:255',
            'calories' => 'sometimes|numeric|min:0',
            'protein' => 'sometimes|numeric|min:0',
            'carbs' => 'sometimes|numeric|min:0',
            'fat' => 'sometimes|numeric|min:0',
            'badge' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:255',
            'serving_size' => 'nullable|string|max:100',
        ];
    }
}
