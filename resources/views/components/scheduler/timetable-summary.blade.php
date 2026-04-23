@props([
    'latestUpload' => null,
    'latestUploadPreviewUrl' => null,
])

<div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between">
        <flux:heading>{{ __('Timetable details') }}</flux:heading>
        <flux:text>
            {{ __('Status:') }}
            <strong>{{ $latestUpload?->status ?? __('not uploaded') }}</strong>
        </flux:text>
    </div>

    @if ($latestUpload?->error_message)
        <flux:text class="mt-3 text-red-600 dark:text-red-400">{{ $latestUpload->error_message }}</flux:text>
    @endif

    @if ($latestUploadPreviewUrl)
        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <img
                src="{{ $latestUploadPreviewUrl }}"
                alt="{{ __('Uploaded timetable preview') }}"
                class="h-auto w-full object-contain"
                loading="lazy"
            />
        </div>
    @elseif ($latestUpload?->original_filename)
        <flux:text class="mt-4">{{ __('File:') }} {{ $latestUpload->original_filename }}</flux:text>
    @endif
</div>
