@php
    $label = is_array($node)
        ? ($node['label'] ?? $node['title'] ?? $node['name'] ?? $node['text'] ?? null)
        : (is_string($node) ? $node : null);
    $children = is_array($node) && isset($node['children']) && is_array($node['children'])
        ? $node['children']
        : [];
@endphp

@if($label !== null)
    <ul class="filament-library-mindmap-tree" @if($depth > 0) style="margin-left: {{ min($depth * 1.25, 6) }}rem;" @endif>
        <li class="filament-library-mindmap-node">
            <span class="filament-library-mindmap-label">{{ $label }}</span>

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
