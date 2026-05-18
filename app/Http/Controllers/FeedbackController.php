<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $feedback = $this->feedbackService->store(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'data' => $feedback,
        ], 201);
    }

    public function myFeedback(Request $request): JsonResponse
    {
        return response()->json($this->feedbackService->getMyFeedback($request->user()->id));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json($this->feedbackService->show($id, $request->user()));
    }

    public function update(UpdateFeedbackRequest $request, int $id): JsonResponse
    {
        $feedback = $this->feedbackService->update(
            $request->validated(),
            $id,
            $request->user()
        );

        return response()->json([
            'message' => 'Feedback updated successfully',
            'data' => $feedback,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->feedbackService->delete($id, $request->user());

        return response()->json([
            'message' => 'Feedback deleted successfully',
        ]);
    }
}
