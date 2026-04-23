@props([
    'slots' => [],
])

@php
    $slots = collect($slots);
    $dayGroups = $slots->groupBy('day_of_week')->sortKeys(SORT_NUMERIC);
    $uniqueSections = $slots->pluck('section')->filter()->unique()->values();
    $uniqueRooms = $slots->pluck('room')->filter()->unique()->values();
    $uniqueBlocks = $slots->pluck('block')->filter()->unique()->values();
    $daysWithClasses = $slots->pluck('day_of_week')->unique()->count();

    $dayLabel = static function (int $dayOfWeek): string {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$dayOfWeek] ?? 'Unknown';
    };

    $formatTime = static function (string $time): string {
        return strtolower(
            \Carbon\CarbonImmutable::createFromFormat('H:i:s', $time)->format('g:i a')
        );
    };
@endphp

<section class="overflow-hidden rounded-3xl border border-cyan-200/60 bg-gradient-to-br from-cyan-50 via-white to-emerald-50 p-5 shadow-sm dark:border-cyan-900/40 dark:from-cyan-950/20 dark:via-zinc-900 dark:to-emerald-950/20">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700 dark:text-cyan-300">{{ __('Teacher workspace') }}</p>
            <flux:heading size="xl">{{ __('Sections, rooms, and assignments') }}</flux:heading>
            <flux:text class="max-w-3xl">{{ __('Use this view to check the section you are teaching, the assigned room and block, and the exact class assignment for each period.') }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2 text-xs font-semibold">
            <span class="rounded-full bg-cyan-100 px-3 py-1.5 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ $uniqueSections->count() }} {{ __('sections') }}</span>
            <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $uniqueRooms->count() }} {{ __('rooms') }}</span>
            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $uniqueBlocks->count() }} {{ __('blocks') }}</span>
            <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">{{ $daysWithClasses }} {{ __('days') }}</span>
        </div>
    </div>

    <div class="mt-5 space-y-4">
        @forelse ($dayGroups as $dayOfWeek => $daySlots)
            <section class="rounded-2xl border border-zinc-200 bg-white/80 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="lg">{{ $dayLabel((int) $dayOfWeek) }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $daySlots->count() }} {{ __('periods') }}</flux:text>
                    </div>

                    <span class="rounded-full bg-cyan-500/10 px-3 py-1.5 text-xs font-semibold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                        {{ $dayLabel((int) $dayOfWeek) }}
                    </span>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($daySlots as $slot)
                        <article wire:key="teacher-slot-{{ $slot->id }}" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/70">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $formatTime($slot->starts_at) }} - {{ $formatTime($slot->ends_at) }}</p>
                                    <flux:heading size="md" class="truncate">{{ $slot->section ?? $slot->course_code ?? $slot->subject }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">{{ $slot->assignment ?? $slot->course_name ?? $slot->subject }}</flux:text>
                                </div>

                                <span class="rounded-full bg-cyan-500/10 px-2 py-1 text-xs font-semibold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                    {{ $slot->course_code ?? __('Class') }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                @if ($slot->section)
                                    <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ $slot->section }}</span>
                                @endif
                                @if ($slot->room)
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ __('Room') }} {{ $slot->room }}</span>
                                @endif
                                @if ($slot->block)
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ $slot->block }}</span>
                                @endif
                                @if ($slot->assignment)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $slot->assignment }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white/80 p-5 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                {{ __('Upload a teacher timetable to see section-wise assignments, room numbers, and blocks here.') }}
            </div>
        @endforelse
    </div>
</section>