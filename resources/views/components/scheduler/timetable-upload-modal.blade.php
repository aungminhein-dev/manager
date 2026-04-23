@props([])

<flux:modal wire:model="showTimetableModal" class="w-full max-w-lg">
    <div class="space-y-4">
        <flux:heading>{{ __('Upload permanent timetable') }}</flux:heading>
        <flux:text>{{ __('Upload a new file to replace your current timetable and regenerate slots.') }}</flux:text>

        <form wire:submit="uploadTimetable" class="space-y-4">
            <flux:input wire:model="timetable" type="file" :label="__('Timetable file')" accept=".pdf,.jpg,.jpeg,.png,.webp" />
            <flux:error name="timetable" />
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Save timetable') }}</flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('showTimetableModal', false)">{{ __('Cancel') }}</flux:button>
            </div>
            <x-action-message on="timetable-uploaded">{{ __('Queued for processing.') }}</x-action-message>
        </form>
    </div>
</flux:modal>

