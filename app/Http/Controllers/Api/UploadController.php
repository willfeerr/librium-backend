<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    use RespondsWithApi;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'path' => ['nullable', 'string', 'max:120'],
        ]);

        $directory = trim($data['path'] ?? 'uploads', '/');
        $path = $request->file('file')->store($directory, config('filesystems.default'));

        return $this->created([
            'path' => $path,
            'url' => Storage::disk(config('filesystems.default'))->url($path),
        ]);
    }
}
