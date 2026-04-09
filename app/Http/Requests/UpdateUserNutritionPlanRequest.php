<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserNutritionPlanRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
            'start_date' => 'sometimes|date|before_or_equal:end_date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'goal_type' => 'sometimes|nullable|string|max:255',
            'active' => 'sometimes|boolean',
        ];
    }
}
