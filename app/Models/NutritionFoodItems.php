<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionFoodItems extends Model
{
    protected $table = 'nutrition_food_items';

    protected $fillable = [
        'nutrition_version_id',
        'nutrition_id',
        'quantity',
        'meal_type',
    ];

    protected $casts = [
        'meal_type' => 'string',
    ];

    public function nutritionVersion()
    {
        return $this->belongsTo(NutritionVersions::class, 'nutrition_version_id');
    }

    public function nutrition()
    {
        return $this->belongsTo(Nutrition::class, 'nutrition_id');
    }
}
