<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralNutritionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required|string|max:255|unique:general_nutrition,category_name,' . $this->id,
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
