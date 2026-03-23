<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
     protected $table = 'suggestions';

    protected $fillable = [
        'feedback_id',
        'status',
    ];
    public function feedback()
    {
        return $this->belongsTo(feedback::class, 'feedback_id');
    }
}
