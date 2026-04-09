<?php

namespace App\Http\Controllers;

use App\Services\SubscriptinService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    Private $subscriptionService;

    public function __construct(SubscriptinService $subscriptionService )
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function show(){
       return $this->subscriptionService->show();

    }
}
