<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramVersionRequest extends FormRequest
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
            'program_exercise_id' => 'nullable|exists:program_exercises,id',
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'source_type' => 'nullable|string|in:injury,ai,goal,coach',
            'source_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }
}
