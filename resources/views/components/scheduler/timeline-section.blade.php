@props([
    'todayTimeline' => [],
])

<div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between">
        <flux:heading>{{ __('Today timeline') }}</flux:heading>
        <flux:text>{{ __('Passed · Ongoing · Upcoming + Free slots') }}</flux:text>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($todayTimeline as $index => $block)
            @php
                $badgeClass = match ($block['status']) {
                    'passed' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                    'ongoing' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                    default => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                };

                $cardClass = $block['type'] === 'free'
                    ? 'border-dashed border-amber-300 bg-amber-50/60 dark:border-amber-700 dark:bg-amber-950/20'
                    : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900';
            @endphp

            <div wire:key="timeline-{{ $index }}" class="rounded-xl border p-3 {{ $cardClass }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading>{{ $block['title'] }}</flux:heading>
                        @if ($block['type'] !== 'free' && !empty($block['subject_name']) && $block['subject_name'] !== $block['subject_code'])
                            <flux:text>{{ $block['subject_name'] }}</flux:text>
                        @endif
                        @if ($block['type'] !== 'free' && !empty($block['section']))
                            <flux:text>{{ __('Section:') }} {{ $block['section'] }}</flux:text>
                        @endif
                        @if ($block['type'] !== 'free' && !empty($block['assignment']))
                            <flux:text>{{ __('Assignment:') }} {{ $block['assignment'] }}</flux:text>
                        @endif
                        @if ($block['type'] !== 'free' && !empty($block['faculty_name']))
                            <flux:text>{{ __('Faculty:') }} {{ $block['faculty_name'] }}</flux:text>
                        @endif
                        @if ($block['subtitle'] !== '')
                            <flux:text>{{ $block['subtitle'] }}</flux:text>
                        @endif
                        <flux:text>{{ $block['starts_at'] }} - {{ $block['ends_at'] }}</flux:text>
                    </div>
                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $badgeClass }}">{{ ucfirst($block['status']) }}</span>
                </div>
            </div>
        @empty
            <flux:text>{{ __('No timetable found for today.') }}</flux:text>
        @endforelse
    </div>
</div>
