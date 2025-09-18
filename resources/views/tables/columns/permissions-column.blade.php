@php
    $permissions = $getState();
@endphp

<div class="flex flex-wrap gap-1">
    @if(empty($permissions))
        <span class="text-xs text-gray-500 italic">No permissions</span>
    @else
        @foreach($permissions as $permissionData)
            @php
                $user = $permissionData['user'];
                $userPermissions = $permissionData['permissions'];
                $isCreator = $permissionData['is_creator'];
            @endphp
            
            <div class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full
                {{ $isCreator ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' }}">
                
                @if($isCreator)
                    <x-heroicon-o-crown class="w-3 h-3" />
                    <span class="font-medium">{{ $user->name }}</span>
                    <span class="text-blue-600 dark:text-blue-400">(Owner)</span>
                @else
                    <x-heroicon-o-user class="w-3 h-3" />
                    <span>{{ $user->name }}</span>
                    
                    @if(in_array('edit', $userPermissions))
                        <x-heroicon-o-pencil class="w-3 h-3 text-green-600" title="Edit" />
                    @elseif(in_array('view', $userPermissions))
                        <x-heroicon-o-eye class="w-3 h-3 text-gray-600" title="View" />
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</div>
