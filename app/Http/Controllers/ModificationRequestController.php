<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModificationRequest;
use App\Http\Requests\UpdateModificationRequest;
use App\Services\ModificationRequestService;
use Illuminate\Http\Request;
use Throwable;

class ModificationRequestController extends Controller
{
    private ModificationRequestService $modificationRequestService;

    public function __construct(ModificationRequestService $modificationRequestService)
    {
        $this->modificationRequestService = $modificationRequestService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $res = $this->modificationRequestService->index();
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModificationRequest $request)
    {
        try {
            $res = $this->modificationRequestService->store($request->validated());
            return response()->json($res, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $res = $this->modificationRequestService->show($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModificationRequest $request, string $id)
    {
        try {
            $res = $this->modificationRequestService->update($request->validated(), $id);
            if ($res) {
                return response()->json(["status" => true, "message" => "Modification request updated successfully"], 200);
            } else {
                return response()->json(["status" => false, "message" => "Update failed"], 400);
            }
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $res = $this->modificationRequestService->destroy($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
