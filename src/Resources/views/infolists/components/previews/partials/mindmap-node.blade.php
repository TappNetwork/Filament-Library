@php
    $label = is_array($node)
        ? ($node['label'] ?? $node['title'] ?? $node['name'] ?? $node['text'] ?? null)
        : (is_string($node) ? $node : null);
    $children = is_array($node) && isset($node['children']) && is_array($node['children'])
        ? $node['children']
        : [];
@endphp

@if($label !== null)
    <ul class="m-0 list-none p-0" @if($depth > 0) style="margin-left: {{ min($depth * 1.25, 6) }}rem;" @endif>
        <li class="my-2">
            <span class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 dark:border-blue-500/40 dark:bg-blue-950/30 dark:text-blue-200">
                {{ $label }}
            </span>

            @foreach($children as $child)
                @include('filament-library::infolists.components.previews.partials.mindmap-node', [
                    'node' => $child,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </li>
    </ul>
@elseif($children !== [])
    @foreach($children as $child)
        @include('filament-library::infolists.components.previews.partials.mindmap-node', [
            'node' => $child,
            'depth' => $depth,
        ])
    @endforeach
@endif
