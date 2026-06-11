@php
    /** @var array<string, mixed> $data */
    $data = $preview->parsedJson ?? [];
    $title = is_string($data['title'] ?? null) ? $data['title'] : null;
    $root = $data['root'] ?? $data;

    $isBranchFormat = false;
    $branches = [];
    $rootTopic = null;

    /** @var list<array{id: string, title: string, elaboration: string, parentTopic: string}> $concepts */
    $concepts = [];

    if (isset($data['branches']) && is_array($data['branches'])) {
        foreach ($data['branches'] as $branch) {
            if (is_array($branch) && isset($branch['leaves']) && is_array($branch['leaves']) && $branch['leaves'] !== []) {
                $isBranchFormat = true;

                break;
            }
        }
    }

    if ($isBranchFormat) {
        $branches = $data['branches'];
        $rootTopic = is_string($data['root_topic'] ?? null)
            ? $data['root_topic']
            : (is_string($data['title'] ?? null) ? $data['title'] : __('Mind map'));

        foreach ($branches as $branchIndex => $branch) {
            if (! is_array($branch)) {
                continue;
            }

            $parentTopic = is_string($branch['title'] ?? null)
                ? $branch['title']
                : (is_string($branch['label'] ?? null) ? $branch['label'] : __('Topic :number', ['number' => $branchIndex + 1]));

            foreach ($branch['leaves'] ?? [] as $leafIndex => $leaf) {
                if (! is_array($leaf)) {
                    continue;
                }

                $leafTitle = is_string($leaf['title'] ?? null)
                    ? $leaf['title']
                    : (is_string($leaf['label'] ?? null) ? $leaf['label'] : __('Concept'));

                $elaboration = is_string($leaf['elaboration'] ?? null)
                    ? $leaf['elaboration']
                    : (is_string($leaf['description'] ?? null) ? $leaf['description'] : '');

                $concepts[] = [
                    'id' => "b{$branchIndex}-l{$leafIndex}",
                    'title' => $leafTitle,
                    'elaboration' => $elaboration,
                    'parentTopic' => $parentTopic,
                ];
            }
        }
    }
@endphp

@if($isBranchFormat)
    <div
        x-data="{
            concepts: @js($concepts),
            selectedId: @js($concepts[0]['id'] ?? null),
            selected() {
                return this.concepts.find((concept) => concept.id === this.selectedId) ?? null;
            },
        }"
        class="filament-library-json-mindmap rounded-lg bg-white p-6 text-gray-950 dark:bg-gray-900 dark:text-white"
    >
        @if($title && $title !== $rootTopic)
            <h2 class="mb-4 text-xl font-semibold text-gray-950 dark:text-white">{{ $title }}</h2>
        @endif

        <div class="mx-auto max-w-3xl rounded-xl border border-amber-200 bg-amber-50/80 px-6 py-4 text-center dark:border-amber-500/40 dark:bg-amber-950/30">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                {{ __('Root topic') }}
            </div>
            <div class="mt-1 text-lg font-semibold leading-snug text-gray-950 dark:text-white">
                {{ $rootTopic }}
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach($branches as $branchIndex => $branch)
                @if(! is_array($branch))
                    @continue
                @endif

                @php
                    $branchTitle = is_string($branch['title'] ?? null)
                        ? $branch['title']
                        : (is_string($branch['label'] ?? null) ? $branch['label'] : __('Topic :number', ['number' => $branchIndex + 1]));
                    $leaves = is_array($branch['leaves'] ?? null) ? $branch['leaves'] : [];
                @endphp

                <div class="flex flex-col gap-3">
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                            {{ __('Topic :number', ['number' => $branchIndex + 1]) }}
                        </div>
                        <div class="mt-1 text-sm font-semibold leading-snug text-gray-950 dark:text-white">
                            {{ $branchTitle }}
                        </div>
                    </div>

                    @if($leaves !== [])
                        <div class="flex flex-wrap gap-2">
                            @foreach($leaves as $leafIndex => $leaf)
                                @if(! is_array($leaf))
                                    @continue
                                @endif

                                @php
                                    $leafTitle = is_string($leaf['title'] ?? null)
                                        ? $leaf['title']
                                        : (is_string($leaf['label'] ?? null) ? $leaf['label'] : __('Concept'));
                                    $conceptId = "b{$branchIndex}-l{$leafIndex}";
                                @endphp

                                <button
                                    type="button"
                                    class="rounded-lg border px-3 py-1.5 text-left text-xs font-medium transition"
                                    :class="selectedId === '{{ $conceptId }}'
                                        ? 'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-500/50 dark:bg-amber-950/40 dark:text-amber-200'
                                        : 'border-gray-200 bg-white text-gray-800 hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:border-white/20'"
                                    @click="selectedId = '{{ $conceptId }}'"
                                >
                                    {{ $leafTitle }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div
            x-show="selected()"
            x-cloak
            class="mt-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5"
        >
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                {{ __('Concept') }}
            </div>
            <div
                x-text="selected()?.title"
                class="mt-1 text-xl font-semibold leading-snug text-gray-950 dark:text-white"
            ></div>
            <p
                x-text="selected()?.elaboration"
                class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-gray-300"
            ></p>
            <div class="mt-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                    {{ __('Parent topic') }}
                </div>
                <span
                    x-text="selected()?.parentTopic"
                    class="mt-2 inline-block rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-900 dark:border-amber-500/40 dark:bg-amber-950/30 dark:text-amber-200"
                ></span>
            </div>
        </div>
    </div>
@else
    <div class="filament-library-json-mindmap rounded-lg bg-white p-6 text-gray-950 dark:bg-gray-900 dark:text-white">
        @if($title)
            <h2 class="mb-4 text-xl font-semibold text-gray-950 dark:text-white">{{ $title }}</h2>
        @endif

        @if(isset($data['nodes']) && is_array($data['nodes']))
            <ul class="m-0 flex list-none flex-col gap-2 p-0">
                @foreach($data['nodes'] as $node)
                    @if(! is_array($node))
                        @continue
                    @endif

                    <li class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 dark:border-blue-500/40 dark:bg-blue-950/30 dark:text-blue-200">
                        {{ $node['label'] ?? $node['title'] ?? $node['name'] ?? $node['text'] ?? __('Node') }}
                    </li>
                @endforeach
            </ul>
        @else
            @include('filament-library::infolists.components.previews.partials.mindmap-node', ['node' => $root, 'depth' => 0])
        @endif
    </div>
@endif
