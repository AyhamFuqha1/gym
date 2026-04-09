<?php

namespace App\Services;

use App\Models\Exercises;
use App\Models\GeneralExercises;

class ExercisesService
{
    public function store($data)
    {
        return Exercises::create($data);
    }

    public function show($id)
    {
        return Exercises::findOrFail($id);
    }

    public function getByGeneralExerciseId($id)
    {
        $generalExercise = GeneralExercises::findOrFail($id);

        $exercises = Exercises::where('general_exercise_id', $id)->get();

        return [
            'success' => true,
            'message' => 'Exercises fetched successfully',
            'generalExercise' => $generalExercise,
            'exercises' => $exercises,
        ];
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
            return [
                "status" => "failed",
                "message" => "This exercise exists in some program and cannot be deleted."
            ];
        }

        $exercise->delete();

        return [
            "status" => "successful",
            "message" => "Exercise deleted successfully."
        ];
    }
}