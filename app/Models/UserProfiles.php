<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfiles extends Model
{
    protected $table = 'user_profiles';

    protected $fillable = [
        'id',
        'user_id',
        'age',
        'height',
        'weight',
        'gender',
        'activity_level',
        'preferences',
        'food_allergies',
        'medical_conditions',

    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    

}
