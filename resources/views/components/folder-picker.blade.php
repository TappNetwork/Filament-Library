<div class="space-y-4">
    <!-- Selected Folder Display -->
    <div class="p-3 bg-gray-50 rounded-lg border">
        <div class="text-sm font-medium text-gray-700 mb-1">Selected Destination:</div>
        <div class="text-lg font-semibold text-gray-900">
            {{ $this->getSelectedFolderName() }}
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="flex items-center space-x-2 text-sm">
        <span class="text-gray-500">Navigate:</span>
        @foreach($this->getBreadcrumbs() as $folderId => $folderName)
            <button
                type="button"
                wire:click="navigateToFolder({{ $folderId }})"
                class="text-blue-600 hover:text-blue-800 hover:underline {{ $folderId === $this->currentFolderId ? 'font-semibold' : '' }}"
            >
                {{ $folderName }}
            </button>
            @if(!$loop->last)
                <span class="text-gray-400">/</span>
            @endif
        @endforeach
    </div>

    <!-- Folder List -->
    <div class="border rounded-lg max-h-64 overflow-y-auto">
        @if($this->getFolders()->count() > 0)
            <div class="divide-y">
                @foreach($this->getFolders() as $folder)
                    <div class="p-3 hover:bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <x-heroicon-s-folder class="w-5 h-5 text-blue-500" />
                            <span class="font-medium text-gray-900">{{ $folder->name }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                wire:click="selectFolder({{ $folder->id }})"
                                class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors"
                            >
                                Select
                            </button>
                            <button
                                type="button"
                                wire:click="navigateToFolder({{ $folder->id }})"
                                class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors"
                            >
                                Open
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                <x-heroicon-o-folder class="w-8 h-8 mx-auto mb-2 text-gray-400" />
                <p>No folders in this location</p>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between pt-4 border-t">
        <button
            type="button"
            wire:click="selectFolder(null)"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
            Select Root
        </button>
        <div class="text-sm text-gray-500">
            Click "Select" to choose a folder or "Open" to navigate into it
        </div>
    </div>
</div>
