<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'general_nutrition_id' => 'required|exists:general_nutrition,id',
            'name' => 'required|string|max:255',
            'calories' => 'required|numeric|min:0',
            'protein' => 'required|numeric|min:0',
            'carbs' => 'required|numeric|min:0',
            'fat' => 'required|numeric|min:0',
            'badge' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:255',
            'serving_size' => 'nullable|string|max:100',
        ];
    }
}
