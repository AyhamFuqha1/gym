<?php

namespace App\Services;

use App\Models\GeneralNutrition;


class GeneralNutritionService
{
    public function index()
    {
        return GeneralNutrition::with('foods')->get();
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

        if ($category->foods()->exists()) {
            return [
                "status" => "failed",
                "message" => "Cannot delete category. It has related foods."
            ];
        }

        $category->delete();
        return [
            "status" => "successfully",
            "message" => "Deleted successfully"
        ];
    }
}
