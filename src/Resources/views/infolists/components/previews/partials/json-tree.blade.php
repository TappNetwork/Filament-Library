@php
    $isList = array_is_list($data);
@endphp

@if($isList)
    <ul class="filament-library-json-tree filament-library-json-tree-list">
        @foreach($data as $index => $item)
            <li class="filament-library-json-tree-item">
                <span class="filament-library-json-tree-key">[{{ $index }}]</span>
                @if(is_array($item))
                    @include('filament-library::infolists.components.previews.partials.json-tree', [
                        'data' => $item,
                        'depth' => $depth + 1,
                    ])
                @else
                    <span class="filament-library-json-tree-value">{{ is_scalar($item) || $item === null ? (string) $item : json_encode($item) }}</span>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <dl class="filament-library-json-tree">
        @foreach($data as $key => $value)
            <div class="filament-library-json-tree-item" style="margin-left: {{ min($depth * 1rem, 4) }}rem;">
                <dt class="filament-library-json-tree-key">{{ $key }}</dt>
                <dd class="filament-library-json-tree-value-block">
                    @if(is_array($value))
                        @include('filament-library::infolists.components.previews.partials.json-tree', [
                            'data' => $value,
                            'depth' => $depth + 1,
                        ])
                    @else
                        <span class="filament-library-json-tree-value">{{ is_scalar($value) || $value === null ? (string) $value : json_encode($value) }}</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endif
