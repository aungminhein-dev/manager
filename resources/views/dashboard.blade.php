@php
    $weekStart = \Carbon\CarbonImmutable::now(config('app.timezone', 'UTC'))->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $weekEnd = $weekStart->addDays(6)->endOfDay();
    $weekDays = collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset));

    $weeklyTodos = auth()->user()->todos()
        ->where('status', 'pending')
        ->with('scheduleSlot')
        ->where(function ($query) use ($weekStart, $weekEnd): void {
            $query->whereBetween('due_at', [$weekStart, $weekEnd])
                ->orWhereBetween('scheduled_for', [$weekStart, $weekEnd]);
        })
        ->orderByRaw('coalesce(due_at, scheduled_for) asc')
        ->orderByDesc('priority_score')
        ->get();

    $todosByDay = $weeklyTodos->groupBy(function ($todo) {
        $effectiveDate = $todo->due_at ?? $todo->scheduled_for;

        return $effectiveDate?->toDateString() ?? 'none';
    });

    $unscheduledTodos = auth()->user()->todos()
        ->where('status', 'pending')
        ->with('scheduleSlot')
        ->whereNull('due_at')
        ->whereNull('scheduled_for')
        ->orderByDesc('priority_score')
        ->get();

    $weekSlots = auth()->user()->scheduleSlots()
        ->where('is_active', true)
        ->orderBy('day_of_week')
        ->orderBy('starts_at')
        ->get();

    $slotsByDay = $weekSlots->groupBy('day_of_week');

    $formatDisplayTime = static function (string $time): string {
        $parsed = \Carbon\CarbonImmutable::createFromFormat('H:i:s', $time);

        return strtolower($parsed->format('g:i a'));
    };

    $parseSubjectParts = static function (?string $subject): array {
        $text = trim((string) $subject);

        if ($text === '') {
            return [null, null, null];
        }

        $faculty = null;
        if (preg_match('/\|\s*Faculty:\s*(.+)$/i', $text, $facultyMatch) === 1) {
            $faculty = trim((string) $facultyMatch[1]);
            $text = trim((string) preg_replace('/\|\s*Faculty:\s*.+$/i', '', $text));
        }

        $code = null;
        $name = null;

        if (str_contains($text, ' - ')) {
            [$left, $right] = explode(' - ', $text, 2);
            $code = trim($left) !== '' ? trim($left) : null;
            $name = trim($right) !== '' ? trim($right) : null;
        } elseif (preg_match('/^[A-Z]{2,}\d{2,}[A-Z0-9-]*$/', $text) === 1) {
            $code = $text;
        } else {
            $name = $text;
        }

        return [$code, $name, $faculty];
    };
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-cyan-50/40 to-emerald-50/30 p-6 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-cyan-950/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-300">
                        {{ __('Dashboard') }}
                    </p>
                    <h1 class="mt-1 text-3xl font-semibold text-zinc-950 dark:text-white">
                        {{ __('Weekly Planner') }}
                    </h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Timetable and todo list for') }} {{ $weekStart->translatedFormat('M j') }} - {{ $weekEnd->translatedFormat('M j, Y') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <flux:badge color="zinc" size="sm">{{ $weekSlots->count() }} {{ $weekSlots->count() === 1 ? __('class') : __('classes') }}</flux:badge>
                    <flux:badge color="emerald" size="sm">{{ $weeklyTodos->count() }} {{ $weeklyTodos->count() === 1 ? __('todo') : __('todos') }}</flux:badge>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                @foreach ($weekDays as $day)
                    @php
                        $daySlots = $slotsByDay[$day->dayOfWeek] ?? collect();
                        $dayTodos = $todosByDay[$day->toDateString()] ?? collect();
                    @endphp

                    <div class="rounded-2xl border border-zinc-200 bg-white/85 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80 {{ $day->isToday() ? 'ring-1 ring-sky-500/30' : '' }}">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                            <div class="xl:w-[9.5rem] xl:shrink-0">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $day->translatedFormat('D') }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <p class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $day->translatedFormat('M j') }}</p>
                                    @if ($day->isToday())
                                        <flux:badge color="sky" size="sm">{{ __('Today') }}</flux:badge>
                                    @endif
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Classes') }}</p>
                                    <div class="flex min-w-0 gap-3 overflow-x-auto pb-1">
                                        @forelse ($daySlots as $slot)
                                            @php
                                                [$slotCode, $slotName, $slotFaculty] = $parseSubjectParts($slot->subject);
                                                $slotTitle = $slot->course_code ?? $slotCode ?? $slot->course_name ?? $slotName ?? __('Class');
                                                $slotSubtitle = $slot->course_name ?? $slotName;
                                            @endphp

                                            <article class="min-w-[23rem] shrink-0 rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/60">
                                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                                    {{ $formatDisplayTime($slot->starts_at) }} - {{ $formatDisplayTime($slot->ends_at) }}
                                                </p>
                                                <h3 class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-zinc-100">
                                                    {{ $slotTitle }}
                                                </h3>

                                                @if ($slotSubtitle && $slotSubtitle !== $slotTitle)
                                                    <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                                        {{ $slotSubtitle }}
                                                    </p>
                                                @endif

                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @if ($slot->room)
                                                        <span class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ __('Room') }} {{ $slot->room }}</span>
                                                    @endif
                                                    @if ($slot->block)
                                                        <span class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ $slot->block }}</span>
                                                    @endif
                                                    @if ($slotFaculty)
                                                        <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-medium text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ $slotFaculty }}</span>
                                                    @endif
                                                </div>
                                            </article>
                                        @empty
                                            <div class="flex min-h-[8.5rem] min-w-[20rem] items-center rounded-2xl border border-dashed border-zinc-300 bg-white/60 px-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
                                                {{ __('No classes for this day.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Todos') }}</p>
                                    <div class="flex min-w-0 gap-2 overflow-x-auto pb-1">
                                        @forelse ($dayTodos as $todo)
                                            @php
                                                $relatedSlot = $todo->scheduleSlot;
                                                [$relatedCode, $relatedName, $relatedFaculty] = $relatedSlot ? $parseSubjectParts($relatedSlot->subject) : [null, null, null];
                                                $subjectLabel = $relatedSlot
                                                    ? ($relatedSlot->course_code ?? $relatedCode ?? $relatedSlot->course_name ?? $relatedName ?? __('Class-related'))
                                                    : null;
                                                $timeLabel = ($todo->due_at ?? $todo->scheduled_for)?->format('g:i a');
                                            @endphp

                                            <div class="inline-flex min-w-max items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm dark:border-emerald-800/50 dark:bg-emerald-900/20">
                                                <span class="max-w-[14rem] truncate font-semibold text-emerald-900 dark:text-emerald-200">{{ $todo->title }}</span>
                                                @if ($subjectLabel)
                                                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $subjectLabel }}</span>
                                                @endif
                                                @if ($timeLabel)
                                                    <span class="whitespace-nowrap text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ $timeLabel }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="flex min-h-[2.75rem] items-center rounded-2xl border border-dashed border-zinc-300 bg-white/60 px-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
                                                {{ __('No todos for this day.') }}
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($unscheduledTodos->isNotEmpty())
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">{{ __('No date yet') }}</p>
                        <flux:badge color="amber" size="sm">{{ $unscheduledTodos->count() }}</flux:badge>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($unscheduledTodos as $todo)
                            <span class="rounded-full border border-amber-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-800 dark:border-amber-800 dark:bg-zinc-900 dark:text-zinc-200">{{ $todo->title }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>
