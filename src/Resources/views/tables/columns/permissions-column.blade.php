@php
    $users = $getState();
@endphp

@if($users && count($users) > 0)
    <div class="space-y-1">
        @foreach($users as $userData)
            @php
                $user = $userData['user'];
                $permissions = $userData['permissions'];
                $isCreator = $userData['is_creator'];
            @endphp

            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <span class="font-medium">{{ $user->name }}</span>
                    @if($isCreator)
                        <x-filament::badge color="primary" size="sm">Owner</x-filament::badge>
                    @endif
                </div>

                <div class="flex space-x-1">
                    @if(in_array('view', $permissions))
                        <x-filament::badge color="gray" size="sm">View</x-filament::badge>
                    @endif
                    @if(in_array('edit', $permissions))
                        <x-filament::badge color="warning" size="sm">Edit</x-filament::badge>
                    @endif
                    @if(in_array('delete', $permissions))
                        <x-filament::badge color="danger" size="sm">Delete</x-filament::badge>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <span class="text-gray-400 text-sm">No specific permissions</span>
@endif
