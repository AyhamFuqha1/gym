<?php

namespace App\Services;

use App\Models\InjuryProgramAdjustments;
use App\Models\UserInjuries;

class UserInjuriesService
{



    public function index()
    {
        return UserInjuries::all();
    }


    public function store($data)
    {
        return UserInjuries::create($data);
    }


    public function show($id)
    {
        return UserInjuries::find($id);
    }


    public function update($id, $data)
    {
        $Injuries = UserInjuries::findOrFail($id);
        return $Injuries->update($data);
    }


    public function destroy($id)
    {
        $Injuries = UserInjuries::findOrFail($id);
        return $Injuries->delete();
    }

    public function dashboard()
    {
        $injuries = UserInjuries::with(['user', 'adjustments.oldExercise', 'adjustments.newExercise'])->paginate(15);

        $injuries->getCollection()->transform(function ($injury) {
            return [
                'id' => $injury->id,
                'user_name' => $injury->user->name,
                'injury_type' => $injury->injury_type, 
                'severity' => $injury->severity, 
                'status' => $injury->status, 

                
                'exercise_restrictions' => $injury->adjustments->map(function ($adj) {
                    return $adj->oldExercise->name ?? 'Unknown Exercise';
                }),

          
                'ai_alternatives' => $injury->adjustments->map(function ($adj) {
                    return $adj->newExercise->name ?? 'No Alternative';
                }),
            ];
        });

        return $injuries;
    }
}
