@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl" class="!text-white">{{ $title }}</flux:heading>
    <flux:subheading class="!text-[#6c757d]">{{ $description }}</flux:subheading>
</div>
