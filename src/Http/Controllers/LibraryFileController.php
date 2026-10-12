<?php

namespace Tapp\FilamentLibrary\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tapp\FilamentLibrary\Models\LibraryItem;

class LibraryFileController
{
    /**
     * Stream a library file to a signed-in user who can already view the item.
     */
    public function show(Request $request, Media $media): StreamedResponse
    {
        $libraryItem = $media->model;

        if (! $libraryItem instanceof LibraryItem) {
            abort(404);
        }

        $user = $request->user();

        if ($user === null || ! Gate::forUser($user)->check('view', $libraryItem)) {
            abort(403);
        }

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mimeType = is_string($media->mime_type) && $media->mime_type !== ''
            ? $media->mime_type
            : 'application/octet-stream';

        $filename = str_replace(['"', "\r", "\n"], '', (string) $media->file_name);

        return $disk->response($path, $filename, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=0, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
