<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNutritionFoodItemRequest extends FormRequest
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
            'nutrition_version_id' => 'required|exists:nutrition_versions,id',
            'nutrition_id' => 'required|exists:nutrition,id',
            'quantity' => 'nullable|string|max:255',
            'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ];
    }
}
