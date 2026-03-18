<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeExerciseRequest;
use App\Models\Exercises;
use App\Services\ExercisesService;
use Illuminate\Http\Request;
use Throwable;

class ExercisesController extends Controller
{
    private ExercisesService $exercisesService;
    public function __construct(ExercisesService $exercisesService)
    {
        $this->exercisesService = $exercisesService;
    }




    public function store(storeExerciseRequest $request)
    {
        try {
            $res = $this->exercisesService->store($request);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $res = $this->exercisesService->show($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $res = $this->exercisesService->update($request, $id);
            if ($res) {
                return response()->json(["status"=>true,"message"=>"update secssefule"], 200);
            } else {
               return response()->json(["status"=>false,"message"=>"update failed"], 403);
            }
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $res = $this->exercisesService->destroy($id);
            return response()->json($res, $res["status"]=="filled"?400:201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
