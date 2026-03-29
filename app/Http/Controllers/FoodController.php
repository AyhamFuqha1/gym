<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use App\Http\Requests\UpdateFoodRequest;
use App\Http\Resources\FoodResource;
use App\Services\FoodService;
use Illuminate\Http\Request;
use Throwable;

class FoodController extends Controller
{
    public FoodService $foodService;

    public function __construct(FoodService $foodService)
    {
        $this->foodService = $foodService;
    }

    public function index(Request $request)
    {
        try {
            $foods = $this->foodService->index($request);
            return FoodResource::collection($foods);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreFoodRequest $request)
    {
        try {
            $data = $request->validated();
            $food = $this->foodService->store($data);
            return new FoodResource($food);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $food = $this->foodService->show($id);
            return new FoodResource($food);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateFoodRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $food = $this->foodService->update($id, $data);
            return new FoodResource($food);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->foodService->destroy($id);
            return response()->json(['message' => 'Food deleted successfully'], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
