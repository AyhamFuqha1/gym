<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseRequest extends FormRequest
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
     *dh.gl,
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'general_exercise_id' => 'sometimes|integer|exists:general_exercises,id',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'video_url' => 'sometimes|nullable|url',
            'instructions' => 'sometimes|string',
            'common_mistakes' => 'sometimes|nullable|string',
        ];
    }
}
