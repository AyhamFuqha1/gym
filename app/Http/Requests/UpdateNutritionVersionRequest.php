<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNutritionVersionRequest extends FormRequest
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
            'user_nutrition_plan_id' => 'sometimes|exists:user_nutrition_plans,id',
            'daily_calories' => 'sometimes|integer|min:1',
            'daily_protein' => 'sometimes|numeric|min:0',
            'daily_carbs' => 'sometimes|numeric|min:0',
            'daily_fat' => 'sometimes|numeric|min:0',
            'reason' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
