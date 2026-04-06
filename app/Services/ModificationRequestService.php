<?php

namespace App\Services;

use App\Models\ModificationRequest;

class ModificationRequestService
{
    public function index()
    {
        return ModificationRequest::with(['user', 'programVersion'])->get();
    }

    public function store($data)
    {
        return ModificationRequest::create($data);
    }

    public function show($id)
    {
        return ModificationRequest::with(['user', 'programVersion'])->findOrFail($id);
    }

    public function update($data, $id)
    {
        $modificationRequest = ModificationRequest::findOrFail($id);
        return $modificationRequest->update($data);
    }

    public function destroy($id)
    {
        $modificationRequest = ModificationRequest::findOrFail($id);
        $modificationRequest->delete();
        return [
            "status" => "successful",
            "message" => "Modification request deleted successfully."
        ];
    }
}