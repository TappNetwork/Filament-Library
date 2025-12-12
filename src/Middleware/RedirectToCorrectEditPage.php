<?php

namespace Tapp\FilamentLibrary\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Tapp\FilamentLibrary\Models\LibraryItem;

class RedirectToCorrectEditPage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return $next($request);
        }

        $panelId = $panel->getId();

        // Check if this is an edit route for library items in any panel
        if ($request->routeIs("filament.{$panelId}.resources.library.edit")) {
            $recordId = $request->route('record');

            if ($recordId) {
                $libraryItem = LibraryItem::find($recordId);

                if ($libraryItem) {
                    // Redirect to the correct edit page based on type
                    $type = $libraryItem->type ?? 'folder';
                    $editUrl = match ($type) {
                        'folder' => route("filament.{$panelId}.resources.library.edit-folder", ['record' => $recordId]),
                        'file' => route("filament.{$panelId}.resources.library.edit-file", ['record' => $recordId]),
                        'link' => route("filament.{$panelId}.resources.library.edit-link", ['record' => $recordId]),
                        default => route("filament.{$panelId}.resources.library.edit-folder", ['record' => $recordId]),
                    };

                    return redirect($editUrl);
                }
            }
        }

        return $next($request);
    }
}
