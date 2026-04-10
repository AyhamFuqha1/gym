<?php

namespace App\Services;

use App\Models\Subscription;

class SubscriptinService
{
   public function show(){
    return Subscription::with([
        'user:id,name',
        'creator:id,name,role_id',
        'creator.role:id,name',
        'plan:id,duration_days,is_active,name'
    ])->get();
   }
}
