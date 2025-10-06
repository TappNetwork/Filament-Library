<?php

namespace Tapp\FilamentLibrary\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tapp\FilamentLibrary\Models\LibraryItem;

class RedirectToCorrectEditPage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is an edit route for library items
        if ($request->routeIs('filament.admin.resources.library.edit')) {
            $recordId = $request->route('record');

            if ($recordId) {
                $libraryItem = LibraryItem::find($recordId);

                if ($libraryItem) {
                    // Redirect to the correct edit page based on type
                    $editUrl = match ($libraryItem->type) {
                        'folder' => route('filament.admin.resources.library.edit-folder', ['record' => $recordId]),
                        'file' => route('filament.admin.resources.library.edit-file', ['record' => $recordId]),
                        'link' => route('filament.admin.resources.library.edit-link', ['record' => $recordId]),
                        default => route('filament.admin.resources.library.edit-folder', ['record' => $recordId]),
                    };

                    return redirect($editUrl);
                }
            }
        }

        return $next($request);
    }
}
