<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
            'goal_type' => 'sometimes|required|string|max:255',
            'target_weight' => 'nullable|numeric|min:0',
        ];
    }
}
