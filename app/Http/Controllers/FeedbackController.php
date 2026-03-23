<?php

namespace App\Http\Controllers;

use App\Models\feedback;
use App\Services\FeedbackService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    private FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService){
        $this->feedbackService=$feedbackService;
    }

    public function dashboard(){
      
            $res=$this->feedbackService->dashboard();
            return response()->json($res,200);

    }
}
