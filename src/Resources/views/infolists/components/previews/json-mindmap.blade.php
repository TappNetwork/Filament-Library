@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $root = $data['root'] ?? $data;
@endphp

<div class="filament-library-json-preview filament-library-json-mindmap">
    @if($title)
        <h2 class="filament-library-json-title">{{ $title }}</h2>
    @endif

    @if(isset($data['nodes']) && is_array($data['nodes']))
        <ul class="filament-library-mindmap-tree">
            @foreach($data['nodes'] as $node)
                @if(! is_array($node))
                    @continue
                @endif
                <li>
                    {{ $node['label'] ?? $node['title'] ?? $node['name'] ?? $node['text'] ?? 'Node' }}
                </li>
            @endforeach
        </ul>
    @else
        @include('filament-library::infolists.components.previews.partials.mindmap-node', ['node' => $root, 'depth' => 0])
    @endif
</div>
