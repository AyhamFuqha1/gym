<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
    ];
    public $timestamps = false;
    public function user()
    {
        return $this->belongsTo(user::class, 'user_id');
    }
}
