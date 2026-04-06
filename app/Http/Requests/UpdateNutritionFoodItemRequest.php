<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNutritionFoodItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nutrition_version_id' => 'sometimes|exists:nutrition_versions,id',
            'nutrition_id' => 'sometimes|exists:nutrition,id',
            'quantity' => 'sometimes|nullable|string|max:255',
            'meal_type' => 'sometimes|in:breakfast,lunch,dinner,snack',
        ];
    }
}
