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
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(user::class, 'user_id');
    }
}
