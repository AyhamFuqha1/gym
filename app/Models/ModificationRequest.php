<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModificationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'program_version_id',
        'type',
        'status',
        'changes_summary',
        'modified_plan',
        'recommendations',
        'user_feedback',
        'source',
        'source_id',
    ];

    protected $casts = [
        'changes_summary' => 'array',
        'modified_plan' => 'array',
        'recommendations' => 'array',
        'user_feedback' => 'array',
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
