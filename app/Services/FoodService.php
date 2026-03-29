<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Http\Request;

class FoodService
{
    public function index(Request $request)
    {
        $query = Food::with('generalNutrition');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category_id')) {
            $query->where('general_nutrition_id', $request->category_id);
        }

        return $query->paginate(15);
    }

    public function store($data)
    {
        return Food::create($data);
    }

    public function show($id)
    {
        return Food::with('generalNutrition')->findOrFail($id);
    }

    public function update($id, $data)
    {
        $food = Food::findOrFail($id);
        $food->update($data);
        return $food;
    }

    public function destroy($id)
    {
        $food = Food::findOrFail($id);
        $food->delete();
        return $food;
    }
}
