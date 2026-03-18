<?php

namespace App\Services;

use App\Models\Exercises;
use App\Models\GeneralExercises;

class GeneralExercisesService
{
    public function index()
    {
        return response()->json([
            'categories_count' => GeneralExercises::count(),
            'exercises_count' => Exercises::count(),
            'categories' => GeneralExercises::all(),
        ]);
    }


    public function store($data)
    {
        return GeneralExercises::create($data);
    }

    public function show($id)
    {
        return GeneralExercises::findOrFail($id);
    }


    public function update($id, $data)
    {
        $generalExercises = GeneralExercises::findOrFail($id);
        $generalExercises->update($data);
        return $generalExercises;
    }


    public function destroy($id)
    {
        $category = GeneralExercises::findOrFail($id);

        if ($category->exercises()->exists()) {
            return [
                "status" => "faild",
                "message" => "Cannot delete category. It has related exercises."
            ];
        }

        $category->delete();
        return [
            "status" => "successfuily",
            "message" => "Deleted Message"
        ];
    }

}
