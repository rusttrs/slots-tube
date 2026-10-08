<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach(\App\Support\Trash::labels() as $key => $label)
            @php
                $class = \App\Support\Trash::models()[$key] ?? null;
                $count = ($class && class_exists($class)) ? $class::onlyTrashed()->count() : 0;
            @endphp
            <button
                type="button"
                wire:click="$set('trashType', '{{ $key }}')"
                @class([
                    'fi-btn fi-size-sm inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium ring-1 ring-inset transition',
                    'bg-warning-600 text-white ring-warning-600' => $trashType === $key,
                    'bg-white text-gray-700 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700' => $trashType !== $key,
                ])
            >
                <span>{{ $label }}</span>
                @if($count > 0)
                    <span @class([
                        'rounded-full px-1.5 text-xs',
                        'bg-white/20' => $trashType === $key,
                        'bg-gray-100 dark:bg-gray-800' => $trashType !== $key,
                    ])>{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
