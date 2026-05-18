<?php

namespace App\Http\Requests;

use App\Models\feedback;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeedbackRequest extends FormRequest
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
            'type' => 'sometimes|in:equipment,suggestion,rating,trainer',
            'content' => 'sometimes|string|max:1000',
        ];

        // Additional validation based on type
        switch ($this->input('type') ?? $this->currentFeedbackType()) {
            case 'equipment':
                $rules['equipment_name'] = 'sometimes|string|max:255';
                $rules['priority'] = 'sometimes|in:low,medium,high';
                $rules['status'] = 'sometimes|in:pending,in_progress,resolved';
                break;
            case 'suggestion':
                $rules['status'] = 'sometimes|in:pending,reviewed,implemented,under_review,resolved';
                break;
            case 'rating':
            case 'trainer':
                $rules['trainer_id'] = 'sometimes|exists:users,id';
                $rules['rating'] = 'sometimes|integer|min:1|max:5';
                break;
        }

        return $rules;
    }

    private function currentFeedbackType(): ?string
    {
        if (!$this->user() || !$this->route('id')) {
            return null;
        }

        $type = feedback::where('id', $this->route('id'))->value('type');

        return $type === 'trainer' ? 'rating' : $type;
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
