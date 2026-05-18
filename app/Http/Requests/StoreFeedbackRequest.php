<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:equipment,suggestion,rating,trainer',
            'content' => 'required|string|max:1000',
        ];

        // Additional validation based on type
        switch ($this->input('type')) {
            case 'equipment':
                $rules['equipment_name'] = 'required|string|max:255';
                $rules['priority'] = 'required|in:low,medium,high';
                $rules['status'] = 'sometimes|in:pending,in_progress,resolved';
                break;
            case 'suggestion':
                $rules['status'] = 'sometimes|in:pending,reviewed,implemented,under_review,resolved';
                break;
            case 'rating':
            case 'trainer':
                $rules['trainer_id'] = 'required|exists:users,id';
                $rules['rating'] = 'required|integer|min:1|max:5';
                break;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be one of: equipment, suggestion, rating',
            'priority.in' => 'Priority must be one of: low, medium, high',
            'status.in' => 'Status must be one of the supported workflow statuses.',
            'rating.min' => 'Rating must be at least 1',
            'rating.max' => 'Rating must be at most 5',
        ];
    }
}
