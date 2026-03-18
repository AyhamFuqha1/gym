<?php

namespace App\Services;

use App\Models\Exercises;

class ExercisesService
{
    /**
     * Create a new class instance.
     */
    public function store($data)
    {
        return Exercises::create($data);
    }


    public function show($id)
    {
        return Exercises::findOrFail($id);
    }


    public function update($data, $id)
    {
        $exer = Exercises::findOrFail($id);
        return $exer->update($data);
    }


    public function destroy($id)
    {

        $exercise = Exercises::findOrFail($id);
        if ($exercise->programs()->exists()) {
            return ([
                "status" => "failed",
                "message" => "This exercise exists in some program and cannot be deleted."
            ]);
        }
        $exercise->delete();
        return ([
            "status" => "successful",
            "message" => "Exercise deleted successfully."
        ] );
    }
}
