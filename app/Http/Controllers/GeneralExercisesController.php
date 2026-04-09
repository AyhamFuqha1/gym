<?php

namespace App\Http\Controllers;

use App\Models\GeneralExercises;
use App\Services\GeneralExercisesService;
use Illuminate\Http\Request;
use Throwable;

class GeneralExercisesController extends Controller
{
    private GeneralExercisesService $generalExercisesService;

    public function __construct(GeneralExercisesService $generalExercisesService)
    {
        $this->generalExercisesService = $generalExercisesService;
    }

    public function index()
    {
        try {
            $res = $this->generalExercisesService->index();
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

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255|unique:general_exercises,name',
                'muscle_group' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $res = $this->generalExercisesService->store($data);
            return response()->json($res, 201);
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
            $res = $this->generalExercisesService->show($id);
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
            $data = $request->validate([
                'name' => 'sometimes|string|max:255|unique:general_exercises,name,' . $id,
                'muscle_group' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
            ]);

            $res = $this->generalExercisesService->update($id, $data);
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

    public function destroy($id)
    {
        try {
            $res = $this->generalExercisesService->destroy($id);
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
}