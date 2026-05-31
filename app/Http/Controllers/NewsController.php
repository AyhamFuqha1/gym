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

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $data = $this->newsService->getPaginatedNews($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function stats()
    {
        $stats = $this->newsService->getNewsStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function store(NewsRequest $data)
    {
        $this->newsService->store($data->validated(), $data->user()?->id);

        return response()->json([
            'status' => 'success',
            'data' => $data->validated(),
        ]);
    }

    public function destroy($id)
    {
        try {
            $news = News::findOrFail($id);
            $wasDeleted = $news->status === 'deleted';

            $this->newsService->destroy($id);

            return response()->json([
                'status' => 'success',
                'message' => $wasDeleted
                    ? 'News permanently deleted successfully'
                    : 'News moved to trash successfully',
                'data' => $id,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found',
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
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'status' => 'sometimes|in:public,draft,deleted',
                'published_at' => 'nullable|date',
                'expires_at' => 'nullable|date',
            ]);

            $updatedNews = $this->newsService->update($id, $validated, $request->user()?->id);

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

    public function show($id)
    {
        try {
            $news = $this->newsService->show($id);

            return response()->json([
                'status' => 'success',
                'data' => $news,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found',
            ], 404);
        }
    }

    public function publicNews(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $data = $this->newsService->getPublicNews($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
