<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsRequest;
use App\Models\News;
use App\Services\NewsServices;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct(NewsServices $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $data = $this->newsService->getPaginatedNews($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Dashboard statistics for news.
     */
    public function stats()
    {
        $stats = $this->newsService->getNewsStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsRequest $data)
    {
        $this->newsService->store($data->validated());

        return response()->json([
            'status' => 'success',
            'data' => $data->validated(),
        ]);
    }


    public function destroy($id)
    {
        try {
            $this->newsService->destroy($id);

            return response()->json([
                'status' => 'success',
                'message' => 'News deleted successfully',
                'data' => $id,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found or already deleted',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
           
            $updatedNews = $this->newsService->update($id, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'News updated successfully',
                'data' => $updatedNews
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }
}
