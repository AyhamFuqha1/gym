<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        'user_id' => 'sometimes|exists:users,id',

        'age' => 'sometimes|integer|min:10|max:100',

        'height' => 'sometimes|numeric|min:100|max:250',

        'weight' => 'sometimes|numeric|min:30|max:300',

        'gender' => 'sometimes|in:male,female',

        'activity_level' => 'sometimes|in:low,moderate,high',

        'preferences' => 'sometimes|nullable|string|max:500',

        'food_allergies' => 'sometimes|nullable|string|max:255',

        'medical_conditions' => 'sometimes|nullable|string|max:255',
    ];
}
}
