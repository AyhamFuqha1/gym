<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgram extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'user_id',
        'program_version_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programVersion()
    {
        return $this->belongsTo(ProgramVersion::class);
    }
}
