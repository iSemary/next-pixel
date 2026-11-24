<?php

namespace App\Http\Controllers;

use App\Http\Requests\Generations\UploadReferenceImageRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReferenceImageController extends Controller
{
    /**
     * Store an uploaded reference image and return its accessible HTTP(S) URL.
     */
    public function store(UploadReferenceImageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('image');

        $path = Storage::disk('public')->putFile("reference-images/{$user->id}", $file);

        $url = rtrim($request->getSchemeAndHttpHost(), '/').'/storage/'.ltrim((string) $path, '/');

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }
}
