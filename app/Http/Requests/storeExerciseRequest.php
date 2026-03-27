<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class storeExerciseRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'general_exercise_id' => 'required|integer|exists:general_exercises,id',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'video_url' => 'nullable|url',
            'instructions' => 'required|string',
            'common_mistakes' => 'nullable|string',
        ];
    }
}
