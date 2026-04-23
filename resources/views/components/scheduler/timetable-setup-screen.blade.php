@props([
    'latestUpload' => null,
    'isTimetableReady' => false,
    'latestUploadPreviewUrl' => null,
])

@php
    $isTeacher = auth()->user()->isRole('teacher');
@endphp

<div>
    @if (! $isTimetableReady)
        <section class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md">
                <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 p-6 shadow-lg dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800">
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <flux:heading size="xl">{{ $isTeacher ? __('Setup Your Teacher Timetable') : __('Setup Your Timetable') }}</flux:heading>
                            <flux:text>
                                {{ $isTeacher
                                    ? __('Upload your teaching timetable to extract sections, room numbers, block labels, and assignments.')
                                    : __('Upload your timetable to get started. This will generate your daily schedule and free slots.') }}
                            </flux:text>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-white/70 p-4 dark:border-zinc-700 dark:bg-zinc-900/70">
                            <div class="mb-3 flex items-center justify-between">
                                <flux:text class="font-semibold">{{ __('Current role:') }}</flux:text>
                                <flux:badge color="emerald">{{ ucfirst(str_replace('_', ' ', (string) auth()->user()->role)) }}</flux:badge>
                            </div>
                            <flux:text class="text-sm">
                                {{ __('Status:') }}
                                <strong>{{ $latestUpload?->status ?? __('not uploaded') }}</strong>
                            </flux:text>
                            @if ($isTeacher)
                                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Teacher slots will show section, assignment, room, and block details after processing.') }}
                                </flux:text>
                            @endif
                            @if ($latestUpload?->error_message)
                                <flux:text class="mt-2 text-red-600 dark:text-red-400">{{ __('Error:') }} {{ $latestUpload->error_message }}</flux:text>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <button 
                                wire:click="openTimetableModal" 
                                type="button"
                                class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-white font-semibold hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2"
                            >
                                <flux:icon icon="arrow-up-tray" />
                                {{ __('Upload Timetable') }}
                            </button>
                            @if ($latestUploadPreviewUrl)
                                <div class="relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <img
                                        src="{{ $latestUploadPreviewUrl }}"
                                        alt="{{ __('Uploaded timetable preview') }}"
                                        class="h-auto w-full object-contain"
                                        loading="lazy"
                                    />

                                    @if (($latestUpload?->status ?? null) === 'processing')
                                        <div class="pointer-events-none absolute inset-0 overflow-hidden">
                                            <div class="absolute left-0 right-0 h-20 bg-gradient-to-b from-transparent via-emerald-300/35 to-transparent animate-[timetable-scan_2.2s_linear_infinite]"></div>
                                        </div>
                                        <div class="pointer-events-none absolute inset-x-0 top-2 text-center">
                                            <span class="rounded-full bg-emerald-500/85 px-2 py-1 text-xs font-semibold text-white">{{ __('Scanning timetable...') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @elseif ($latestUpload?->original_filename)
                                <flux:text class="text-center text-sm">{{ __('Current file:') }} <strong>{{ $latestUpload->original_filename }}</strong></flux:text>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>

<style>
    @keyframes timetable-scan {
        0% {
            transform: translateY(-130%);
        }
        100% {
            transform: translateY(300%);
        }
    }
</style>
