<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-4">
        <!-- Breadcrumb Navigation -->
        <div class="flex items-center space-x-2 text-sm">
            <span class="text-gray-500">Navigate:</span>
            @foreach($getBreadcrumbs() as $folderId => $folderName)
                <a
                    href="{{ request()->fullUrlWithQuery(['current_folder' => $folderId]) }}"
                    class="text-blue-600 hover:text-blue-800 hover:underline {{ $folderId === $getCurrentFolderId() ? 'font-semibold' : '' }}"
                >
                    {{ $folderName }}
                </a>
                @if(!$loop->last)
                    <span class="text-gray-400">/</span>
                @endif
            @endforeach
        </div>

        <!-- Filament Table Component -->
        {{ $this->table }}

        <!-- Root Selection -->
        <div class="flex justify-start">
            <button
                type="button"
                onclick="moveToRoot()"
                class="fi-btn fi-btn-color-gray fi-btn-size-sm fi-btn-outlined"
            >
                <span class="fi-btn-label">Move to Root</span>
            </button>
        </div>
    </div>

    <script>
        function moveToRoot() {
            // Set the form field value to null (root)
            const form = document.querySelector('form');
            const input = form.querySelector('input[name="data.parent_id"]');
            if (input) {
                input.value = '';
            }

            // Close the modal and submit
            window.dispatchEvent(new CustomEvent('close-modal', {
                detail: { id: 'filament.forms.actions.move' }
            }));
        }
    </script>
</x-dynamic-component>




