<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'general_nutrition_id',
        'name',
        'calories',
        'protein',
        'carbs',
        'fat',
        'badge',
        'image',
        'serving_size',
    ];

    protected $casts = [
        'calories' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
    ];

    public function generalNutrition()
    {
        return $this->belongsTo(GeneralNutrition::class, 'general_nutrition_id');
    }
}
