@props([])

@php
    $isTeacher = auth()->user()->isRole('teacher');
@endphp

<div class="overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 p-5 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl">{{ $isTeacher ? __('Teacher Scheduler') : __('Daily Scheduler') }}</flux:heading>
            <flux:text>
                {{ $isTeacher
                    ? __('Track sections, room numbers, block assignments, and AI-prioritized todos in one place.')
                    : __('Track class periods, free slots, and AI-prioritized todos in one place.') }}
            </flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:badge color="emerald">{{ ucfirst(str_replace('_', ' ', (string) auth()->user()->role)) }}</flux:badge>
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="openTimetableModal">
                {{ __('Update timetable') }}
            </flux:button>
        </div>
    </div>

</div>
