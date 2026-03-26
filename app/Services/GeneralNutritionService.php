<?php

namespace App\Services;

use App\Models\GeneralNutrition;
use App\Models\Nutrition;  // Assuming Nutrition model exists based on migration pattern

class GeneralNutritionService
{
    public function index()
    {
        return GeneralNutrition::all();
    }

    public function store($data)
    {
        return GeneralNutrition::create($data);
    }

    public function show($id)
    {
        return GeneralNutrition::findOrFail($id);
    }

    public function update($id, $data)
    {
        $generalNutrition = GeneralNutrition::findOrFail($id);
        $generalNutrition->update($data);
        return $generalNutrition;
    }

    public function destroy($id)
    {
        $category = GeneralNutrition::findOrFail($id);

        if ($category->nutritions()->exists()) {
            return [
                "status" => "failed",
                "message" => "Cannot delete category. It has related nutritions."
            ];
        }

        $category->delete();
        return [
            "status" => "successfully",
            "message" => "Deleted successfully"
        ];
    }
}
