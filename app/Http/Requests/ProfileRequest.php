<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
        'user_id' => 'required|exists:users,id',

        'age' => 'required|integer|min:10|max:100',

        'height' => 'required|numeric|min:100|max:250', 

        'weight' => 'required|numeric|min:30|max:300', 

        'gender' => 'required|in:male,female',

        'activity_level' => 'required|in:low,moderate,high',

        'preferences' => 'nullable|string|max:500',

        'food_allergies' => 'nullable|string|max:255',

        'medical_conditions' => 'nullable|string|max:255',
    ];
}
}
