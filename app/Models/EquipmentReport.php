<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentReport extends Model
{
     protected $table = 'equipment_reports';

    protected $fillable = [
        'feedback_id',
        'equipment_name',
        'priority',
        'status',
      
    ];
    public function feedback()
    {
        return $this->belongsTo(feedback::class, 'feedback_id');
    }
}
