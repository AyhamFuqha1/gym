<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class feedback extends Model
{

 protected $table = 'user_feedback';

    protected $fillable = [
        'user_id',
        'type',
        'content',
        
    ];
    public function equipment()
    {
        return $this->hasOne(EquipmentReport::class, 'feedback_id');
    }

    public function trainerRating()
    {
        return $this->hasOne(TrainerRating::class, 'feedback_id');
    }

    public function suggestion()
    {
        return $this->hasOne(Suggestion::class, 'feedback_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
