@props([
    'summary' => [],
    'categoryBreakdown' => [],
    'focusTodos' => [],
])

@php
    $progressWidth = function (int $value, int $total): string {
        if ($total <= 0) {
            return '0%';
        }

        return min(100, max(6, (int) round(($value / $total) * 100))).'%';
    };

    $metricStyles = [
        'pending' => [
            'card' => 'from-zinc-500/10 to-zinc-100/70 ring-zinc-200/70',
            'label' => 'text-zinc-500 dark:text-zinc-400',
            'value' => 'text-zinc-900 dark:text-zinc-50',
        ],
        'due_today' => [
            'card' => 'from-amber-500/15 to-amber-100/70 ring-amber-200/70',
            'label' => 'text-amber-600 dark:text-amber-300',
            'value' => 'text-amber-700 dark:text-amber-300',
        ],
        'overdue' => [
            'card' => 'from-rose-500/15 to-rose-100/70 ring-rose-200/70',
            'label' => 'text-rose-600 dark:text-rose-300',
            'value' => 'text-rose-700 dark:text-rose-300',
        ],
        'completed_this_week' => [
            'card' => 'from-emerald-500/15 to-emerald-100/70 ring-emerald-200/70',
            'label' => 'text-emerald-600 dark:text-emerald-300',
            'value' => 'text-emerald-700 dark:text-emerald-300',
        ],
    ];

    $barStyles = [
        [
            'pill' => 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            'bar' => 'bg-emerald-500',
        ],
        [
            'pill' => 'bg-cyan-500/10 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
            'bar' => 'bg-cyan-500',
        ],
        [
            'pill' => 'bg-amber-500/10 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
            'bar' => 'bg-amber-500',
        ],
        [
            'pill' => 'bg-rose-500/10 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            'bar' => 'bg-rose-500',
        ],
        [
            'pill' => 'bg-violet-500/10 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
            'bar' => 'bg-violet-500',
        ],
        [
            'pill' => 'bg-sky-500/10 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
            'bar' => 'bg-sky-500',
        ],
    ];

    $focusTask = $focusTodos[0] ?? null;
    $totalCategoryCount = collect($categoryBreakdown)->sum('count');
@endphp

<aside class="space-y-4 rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-zinc-50 to-cyan-50/70 p-5 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-cyan-950/20 xl:sticky xl:top-6">
    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Productivity Pulse') }}</flux:heading>
        <flux:text>{{ __('A focused view of what matters now, what is due next, and how your workload is shaping up.') }}</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-3">
        @foreach ([
            'pending' => __('Open tasks'),
            'due_today' => __('Due today'),
            'overdue' => __('Overdue'),
            'completed_this_week' => __('Done this week'),
        ] as $key => $label)
            @php $tone = $metricStyles[$key]; @endphp

            <div class="rounded-2xl border border-white/70 bg-gradient-to-br {{ $tone['card'] }} p-3 shadow-sm ring-1 dark:border-zinc-700/60 dark:bg-zinc-950/70">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $tone['label'] }}">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold {{ $tone['value'] }}">{{ number_format((int) ($summary[$key] ?? 0)) }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/70">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Focus mode') }}</p>
                <flux:heading size="md">{{ __('Next best action') }}</flux:heading>
            </div>
            <flux:badge color="emerald">{{ (int) ($summary['completion_rate'] ?? 0) }}%</flux:badge>
        </div>

        <p class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">
            {{ (int) ($summary['due_soon'] ?? 0) }} {{ __('tasks due soon') }}
        </p>

        @if ($focusTask)
            <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-cyan-50 p-4 dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-cyan-950/20">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">{{ __('Highest priority') }}</p>
                        <p class="truncate text-base font-semibold text-zinc-950 dark:text-zinc-50">{{ $focusTask->title }}</p>
                        @if ($focusTask->description)
                            <p class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $focusTask->description }}</p>
                        @endif
                    </div>

                    <flux:badge color="amber" size="sm" class="shrink-0">{{ $focusTask->priority_score }}</flux:badge>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                    @if ($focusTask->category)
                        <span class="rounded-full bg-white/80 px-2 py-1 font-medium dark:bg-zinc-900/80">{{ \Illuminate\Support\Str::headline($focusTask->category) }}</span>
                    @endif
                    @if ($focusTask->due_at)
                        <span class="rounded-full bg-white/80 px-2 py-1 font-medium dark:bg-zinc-900/80">{{ __('Due') }} {{ strtolower($focusTask->due_at->format('M j, g:i a')) }}</span>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                {{ __('No open tasks. Add a new todo and it will appear here.') }}
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/70">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Category mix') }}</p>
                <flux:heading size="md">{{ __('Where your effort goes') }}</flux:heading>
            </div>
            <flux:badge color="zinc">{{ number_format($totalCategoryCount) }}</flux:badge>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($categoryBreakdown as $index => $category)
                @php $style = $barStyles[$index % count($barStyles)]; @endphp

                <div wire:key="todo-category-{{ $category['key'] }}" class="space-y-1.5">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $category['label'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $category['count'] }} {{ __('tasks') }}</p>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $style['pill'] }}">{{ $category['percentage'] }}%</span>
                    </div>
                    <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">{{ __('Priority avg') }} {{ $category['priority'] }}</p>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full {{ $style['bar'] }} transition-all" style="width: {{ $progressWidth((int) $category['count'], max(1, $totalCategoryCount)) }}"></div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                    {{ __('Category data will appear here after a few tasks are created.') }}
                </div>
            @endforelse
        </div>
    </div>

    @if (count($focusTodos) > 0)
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/70">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Priority queue') }}</p>
                    <flux:heading size="md">{{ __('Top tasks') }}</flux:heading>
                </div>
                <flux:icon icon="bars-3" class="size-5 text-zinc-400" />
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($focusTodos as $todo)
                    <div wire:key="focus-todo-{{ $todo->id }}" class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <p class="truncate text-sm font-semibold text-zinc-950 dark:text-zinc-100">{{ $todo->title }}</p>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                                    @if ($todo->category)
                                        <span class="rounded-full bg-white px-2 py-1 font-medium dark:bg-zinc-950">{{ \Illuminate\Support\Str::headline($todo->category) }}</span>
                                    @endif
                                    @if ($todo->due_at)
                                        <span class="rounded-full bg-white px-2 py-1 font-medium dark:bg-zinc-950">{{ strtolower($todo->due_at->format('M j, g:i a')) }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ $todo->priority_score }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</aside>