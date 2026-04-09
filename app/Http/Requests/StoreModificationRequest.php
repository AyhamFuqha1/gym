<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModificationRequest extends FormRequest
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
            'program_version_id' => 'required|exists:program_versions,id',
            'changes_summary' => 'nullable|array',
            'modified_plan' => 'nullable|array',
            'recommendations' => 'nullable|array',
            'user_feedback' => 'nullable|array',
            'source' => 'nullable|string',
            'source_id' => 'nullable|integer',
        ];
    }
}
