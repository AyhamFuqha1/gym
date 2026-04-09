<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
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
            $res = $this->exercisesService->store($request->validated());
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

    public function getByGeneralExerciseId($id)
    {
        try {
            $res = $this->exercisesService->getByGeneralExerciseId($id);
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

    public function update(UpdateExerciseRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $res = $this->exercisesService->update($data, $id);

            if ($res) {
                return response()->json(["status" => true, "message" => "update successful"], 200);
            } else {
                return response()->json(["status" => false, "message" => "update failed"], 403);
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
            return response()->json($res, $res["status"] == "failed" ? 400 : 200);
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