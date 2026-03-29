<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nutrition extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'general_nutrition_id',
        'name',
        'calories',
        'protein',
        'carbs',
        'fat',
    ];

    public function generalNutrition()
    {
        return $this->belongsTo(GeneralNutrition::class, 'general_nutrition_id');
    }

    public function nutritionVersions()
    {
        return $this->belongsToMany(
            NutritionVersions::class,
            'nutrition_food_items',
            'nutrition_version_id',
            'nutrition_id'
        )
            ->withPivot('quantity', 'meal_type')
            ->withTimestamps();
    }
}