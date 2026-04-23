@props([
    'currentSlot' => null,
    'nextSlot' => null,
    'suggestedSlots' => [],
    'slotTodoMatches' => [],
])

@php
    $formatDisplayTime = static function (string $time): string {
        $parsed = \Carbon\CarbonImmutable::createFromFormat('H:i:s', $time);

        return strtolower($parsed->format('g:i a'));
    };

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

    $normalizeRoomAndBlock = static function (?string $room, ?string $block): array {
        $room = $room !== null ? trim($room) : null;
        $block = $block !== null ? trim($block) : null;

        if ($room !== null && preg_match('/^(?<room>.*?)[\\s-]*block\\s*(?<block>[a-z0-9]+)/i', $room, $matches) === 1) {
            $roomCandidate = trim((string) ($matches['room'] ?? ''), " -\t\n\r\0\x0B");
            $blockCandidate = trim((string) ($matches['block'] ?? ''));

            if ($roomCandidate !== '') {
                $room = $roomCandidate;
            }

            if (($block === null || $block === '') && $blockCandidate !== '') {
                $block = 'Block '.$blockCandidate;
            }
        }

        if ($block !== null && $block !== '' && ! str_starts_with(strtolower($block), 'block')) {
            $block = 'Block '.$block;
        }

        return [$room, $block];
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

<div class="space-y-4">
    <div class="overflow-hidden rounded-2xl border border-cyan-200/50 bg-gradient-to-br from-cyan-100/50 via-white to-emerald-100/50 p-4 shadow-sm dark:border-cyan-900/40 dark:from-cyan-950/30 dark:via-zinc-900 dark:to-emerald-950/20">
        <div class="mb-3 flex items-center justify-between">
            <flux:heading>{{ __('Next class') }}</flux:heading>
            <span class="rounded-full bg-cyan-500/15 px-3 py-1 text-xs font-semibold text-cyan-700 dark:text-cyan-300">{{ __('Class lineup') }}</span>
        </div>

        @if ($nextSlot)
            @php
                [$roomDisplay, $blockDisplay] = $normalizeRoomAndBlock($nextSlot->room, $nextSlot->block);
                [$parsedNextCode, $parsedNextName, $parsedNextFaculty] = $parseSubjectParts($nextSlot->subject);
                $nextCode = $nextSlot->course_code ?? $parsedNextCode;
                $nextName = $nextSlot->course_name ?? $parsedNextName;
                $nextFaculty = $nextSlot->faculty_name ?? $parsedNextFaculty;
                $nextSection = $nextSlot->section;
                $nextAssignment = $nextSlot->assignment;
                $nextTodos = $slotTodoMatches[$nextSlot->id] ?? [];
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white/80 p-4 dark:border-zinc-700 dark:bg-zinc-900/80">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Coming next') }}</p>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $nextCode ?? __('Class') }}</h3>
                        @if ($nextName && $nextName !== $nextCode)
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $nextName }}</p>
                        @endif
                    </div>
                    <span class="inline-flex w-max shrink-0 whitespace-nowrap rounded-lg bg-emerald-500/15 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $formatDisplayTime($nextSlot->starts_at) }}</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                    <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ $dayLabel((int) $nextSlot->day_of_week) }}</span>
                    @if ($nextSection)
                        <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ __('Section') }} {{ $nextSection }}</span>
                    @endif
                    @if ($roomDisplay)
                        <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ __('Room') }} {{ $roomDisplay }}</span>
                    @endif
                    @if ($blockDisplay)
                        <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ $blockDisplay }}</span>
                    @endif
                    @if ($nextAssignment)
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $nextAssignment }}</span>
                    @endif
                    @if ($nextFaculty)
                        <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ __('Faculty:') }} {{ $nextFaculty }}</span>
                    @endif
                </div>

                @if (count($nextTodos) > 0)
                    <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Attached tasks') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($nextTodos as $todo)
                                <span class="inline-flex max-w-full items-center rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <span class="truncate">{{ $todo['title'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <flux:text class="mt-2">{{ __('No upcoming class found.') }}</flux:text>
        @endif
    </div>

    @if (count($suggestedSlots) > 0)
        <div class="space-y-2">
            <flux:text class="font-semibold">{{ __('Upcoming classes') }}</flux:text>
            <div class="flex snap-x gap-3 overflow-x-auto pb-1">
                @foreach ($suggestedSlots as $slot)
                    @if ($nextSlot && $slot->id === $nextSlot->id)
                        @continue
                    @endif
                    @php
                        [$slotRoomDisplay, $slotBlockDisplay] = $normalizeRoomAndBlock($slot->room, $slot->block);
                        [$pCode, $pName, $pFaculty] = $parseSubjectParts($slot->subject);
                        $code = $slot->course_code ?? $pCode;
                        $name = $slot->course_name ?? $pName;
                        $slotSection = $slot->section;
                        $slotAssignment = $slot->assignment;
                            $slotTodos = $slotTodoMatches[$slot->id] ?? [];
                    @endphp
                    <div wire:key="suggested-slot-{{ $slot->id }}" class="min-w-[220px] snap-start rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs text-zinc-500">{{ $dayLabel((int) $slot->day_of_week) }} · {{ $formatDisplayTime($slot->starts_at) }}</p>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $code ?? __('Class') }}</p>
                        @if ($name && $name !== $code)
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $name }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                            @if ($slotSection)
                                <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">{{ $slotSection }}</span>
                            @endif
                            @if ($slotRoomDisplay)
                                <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ __('Room') }} {{ $slotRoomDisplay }}</span>
                            @endif
                            @if ($slotBlockDisplay)
                                <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">{{ $slotBlockDisplay }}</span>
                            @endif
                            @if ($slotAssignment)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $slotAssignment }}</span>
                            @endif
                        </div>
                            @if (count($slotTodos) > 0)
                                <div class="mt-3 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Attached tasks') }}</p>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($slotTodos as $todo)
                                            <div class="rounded-lg bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                                <span class="block truncate">{{ $todo['title'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading>{{ __('Current status') }}</flux:heading>
        @if ($currentSlot)
            @php
                [$parsedCurrentCode, $parsedCurrentName, $parsedCurrentFaculty] = $parseSubjectParts($currentSlot->subject);
                $currentCode = $currentSlot->course_code ?? $parsedCurrentCode;
                $currentName = $currentSlot->course_name ?? $parsedCurrentName;
                $currentFaculty = $currentSlot->faculty_name ?? $parsedCurrentFaculty;
                $currentSection = $currentSlot->section;
                $currentAssignment = $currentSlot->assignment;
                $currentTodos = $slotTodoMatches[$currentSlot->id] ?? [];
            @endphp
            <flux:text class="mt-2">
                {{ __('Ongoing:') }}
                {{ $currentCode ?? $currentName ?? __('Class') }}
                ({{ $formatDisplayTime($currentSlot->starts_at) }} - {{ $formatDisplayTime($currentSlot->ends_at) }})
            </flux:text>
            @if ($currentName && $currentName !== $currentCode)
                <flux:text>{{ $currentName }}</flux:text>
            @endif
            @if ($currentFaculty)
                <flux:text>{{ __('Faculty:') }} {{ $currentFaculty }}</flux:text>
            @endif
            @if ($currentSection)
                <flux:text>{{ __('Section:') }} {{ $currentSection }}</flux:text>
            @endif
            @if ($currentAssignment)
                <flux:text>{{ __('Assignment:') }} {{ $currentAssignment }}</flux:text>
            @endif
            @if (count($currentTodos) > 0)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($currentTodos as $todo)
                        <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ $todo['title'] }}</span>
                    @endforeach
                </div>
            @endif
        @else
            <flux:text class="mt-2">{{ __('No works right now.') }}</flux:text>
        @endif
    </div>
</div>
