<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramVersionRequest extends FormRequest
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
            'program_exercise_id' => 'sometimes|nullable|exists:program_exercises,id',
            'name' => 'sometimes|string|max:255',
            'level' => 'sometimes|string|max:255',
            'source_type' => 'sometimes|nullable|string|in:injury,ai,goal,coach',
            'source_id' => 'sometimes|nullable|integer',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
