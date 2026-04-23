<?php

use App\Actions\Scheduler\GeminiSchedulerClient;
use App\Jobs\ProcessTimetableUpload;
use App\Models\ScheduleSlot;
use App\Models\TimetableUpload;
use App\Models\Todo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Scheduler')] class extends Component {
    use AuthorizesRequests;
    use WithFileUploads;

    public ?TemporaryUploadedFile $timetable = null;

    public bool $showTimetableModal = false;
    public bool $uploadQueued = false;

    public function openTimetableModal(): void
    {
        $this->uploadQueued = false;
        $this->showTimetableModal = true;
    }

    public function uploadTimetable(): void
    {
        $this->authorize('create', TimetableUpload::class);

        $this->validate([
            'timetable' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $path = $this->timetable->store('timetables', 'local');

        $upload = TimetableUpload::query()->create([
            'user_id' => Auth::id(),
            'original_filename' => $this->timetable->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $this->resolveUploadMimeType($this->timetable),
            'status' => 'pending',
        ]);

        ProcessTimetableUpload::dispatch($upload->id);

        $this->reset('timetable');
        $this->uploadQueued = true;
        $this->showTimetableModal = false;
        $this->dispatch('timetable-uploaded');
    }

    protected function resolveUploadMimeType(TemporaryUploadedFile $file): string
    {
        $candidates = [
            trim((string) $file->getClientMimeType()),
            trim((string) $file->getMimeType()),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && ! $this->isGenericMimeType($candidate)) {
                return $candidate;
            }
        }

        return match (strtolower((string) $file->getClientOriginalExtension())) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/pdf',
        };
    }

    protected function isGenericMimeType(string $mimeType): bool
    {
        $normalized = strtolower(trim($mimeType));

        return in_array($normalized, [
            'application/octet-stream',
            'binary/octet-stream',
            'application/unknown',
        ], true);
    }

    #[Computed]
    public function latestUpload(): ?TimetableUpload
    {
        return Auth::user()->timetableUploads()->latest('id')->first();
    }

    #[Computed]
    public function isTimetableReady(): bool
    {
        $upload = $this->latestUpload;

        return $upload
            && in_array($upload->status, ['completed', 'processed'], true)
            && ! $upload->error_message;
    }

    #[Computed]
    public function currentSlot(): ?ScheduleSlot
    {
        $now = $this->nowInSchedulerTimezone();
        $time = $now->format('H:i:s');

        return Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->where('day_of_week', $now->dayOfWeek)
            ->where('starts_at', '<=', $time)
            ->where('ends_at', '>', $time)
            ->orderBy('starts_at')
            ->first();
    }

    #[Computed]
    public function nextSlot(): ?ScheduleSlot
    {
        $now = $this->nowInSchedulerTimezone();
        $time = $now->format('H:i:s');

        $todayNext = Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->where('day_of_week', $now->dayOfWeek)
            ->where('starts_at', '>', $time)
            ->orderBy('starts_at')
            ->first();

        if ($todayNext !== null) {
            return $todayNext;
        }

        for ($offset = 1; $offset <= 7; $offset++) {
            $day = ($now->dayOfWeek + $offset) % 7;

            $slot = Auth::user()->scheduleSlots()
                ->where('is_active', true)
                ->where('day_of_week', $day)
                ->orderBy('starts_at')
                ->first();

            if ($slot !== null) {
                return $slot;
            }
        }

        return null;
    }

    #[Computed]
    public function pendingTodos(): EloquentCollection
    {
        return Auth::user()->todos()
            ->where('status', 'pending')
            ->orderByDesc('priority_score')
            ->orderBy('due_at')
            ->get();
    }

    #[Computed]
    public function slotTodoMatches(): array
    {
        $slots = Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->with('subjectRef')
            ->get();

        $matches = $slots
            ->mapWithKeys(fn (ScheduleSlot $slot): array => [$slot->id => collect()])
            ->all();

        foreach ($this->pendingTodos as $todo) {
            $explicitSlot = $todo->schedule_slot_id !== null
                ? $slots->firstWhere('id', (int) $todo->schedule_slot_id)
                : null;

            if ($explicitSlot !== null) {
                $targetSlot = $this->nearestUpcomingSlotForSubject($slots, $this->subjectKeyForSlot($explicitSlot));

                if ($targetSlot !== null) {
                    $matches[$targetSlot->id]->push($this->formatTodoMatch($todo, 100));
                }

                continue;
            }

            $best = $slots
                ->map(function (ScheduleSlot $slot) use ($todo): array {
                    return [
                        'slot_id' => $slot->id,
                        'score' => $this->matchTodoToSlot($todo, $slot),
                    ];
                })
                ->sortByDesc('score')
                ->first();

            if (! is_array($best) || ($best['score'] ?? 0) < 18) {
                continue;
            }

            $matches[$best['slot_id']]->push($this->formatTodoMatch($todo, (int) $best['score']));
        }

        foreach ($matches as $slotId => $items) {
            $matches[$slotId] = $items
                ->sortByDesc('score')
                ->take(3)
                ->values()
                ->all();
        }

        return $matches;
    }

    #[Computed]
    public function suggestedSlots(): Collection
    {
        $now = $this->nowInSchedulerTimezone();
        $time = $now->format('H:i:s');
        return Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->where('day_of_week', $now->dayOfWeek)
            ->where('starts_at', '>', $time)
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function todaySlots(): EloquentCollection
    {
        $now = $this->nowInSchedulerTimezone();

        return Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->where('day_of_week', $now->dayOfWeek)
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function teacherScheduleSlots(): EloquentCollection
    {
        return Auth::user()->scheduleSlots()
            ->where('is_active', true)
            ->with('subjectRef')
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function todayTimeline(): Collection
    {
        $now = $this->nowInSchedulerTimezone();
        $dayStart = $now->startOfDay()->setTime(6, 0);
        $dayEnd = $now->startOfDay()->setTime(22, 0);

        $timeline = collect();
        $cursor = $dayStart;

        foreach ($this->todaySlots as $slot) {
            $slotStart = $this->combineTodayWithTime($slot->starts_at);
            $slotEnd = $this->combineTodayWithTime($slot->ends_at);

            if ($slotStart->greaterThan($cursor)) {
                $timeline->push($this->makeTimelineBlock('free', $cursor, $slotStart));
            }

            $timeline->push($this->makeTimelineBlock('class', $slotStart, $slotEnd, $slot));
            $cursor = $slotEnd->greaterThan($cursor) ? $slotEnd : $cursor;
        }

        if ($cursor->lessThan($dayEnd)) {
            $timeline->push($this->makeTimelineBlock('free', $cursor, $dayEnd));
        }

        return $timeline;
    }

    protected function makeTimelineBlock(string $type, CarbonImmutable $start, CarbonImmutable $end, ?ScheduleSlot $slot = null): array
    {
        $subjectParts = $type === 'free'
            ? ['code' => null, 'name' => null, 'faculty' => null, 'section' => null, 'assignment' => null]
            : $this->resolveSubjectParts($slot);

        return [
            'type' => $type,
            'status' => $this->statusForRange($start, $end),
            'starts_at' => $this->formatTimeRangeValue($start),
            'ends_at' => $this->formatTimeRangeValue($end),
            'title' => $type === 'free' ? 'Free slot' : (string) ($subjectParts['code'] ?? $subjectParts['name'] ?? $slot?->subject),
            'subject_code' => $subjectParts['code'],
            'subject_name' => $subjectParts['name'],
            'faculty_name' => $subjectParts['faculty'],
            'section' => $subjectParts['section'],
            'assignment' => $subjectParts['assignment'],
            'subtitle' => $type === 'free'
                ? 'Use this block for high-priority tasks.'
                : trim(collect([$slot?->room ? 'Room '.$slot->room : null, $slot?->block ? 'Block '.$slot->block : null])->filter()->implode(' · ')),
        ];
    }

    /**
     * @return array{code:?string,name:?string,faculty:?string,section:?string,assignment:?string}
     */
    protected function resolveSubjectParts(?ScheduleSlot $slot): array
    {
        if ($slot === null) {
            return ['code' => null, 'name' => null, 'faculty' => null, 'section' => null, 'assignment' => null];
        }

        $fallback = $this->parseSubjectParts((string) $slot->subject);

        return [
            'code' => $slot->course_code ?: $fallback['code'],
            'name' => $slot->course_name ?: $fallback['name'],
            'faculty' => $slot->faculty_name ?: $fallback['faculty'],
            'section' => $slot->section,
            'assignment' => $slot->assignment,
        ];
    }

    /**
     * @return array{code:?string,name:?string,faculty:?string}
     */
    protected function parseSubjectParts(string $subject): array
    {
        $text = trim($subject);

        if ($text === '') {
            return ['code' => null, 'name' => null, 'faculty' => null, 'section' => null, 'assignment' => null];
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

        return [
            'code' => $code,
            'name' => $name,
            'faculty' => $faculty,
        ];
    }

    protected function statusForRange(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $now = $this->nowInSchedulerTimezone();

        if ($end->lessThanOrEqualTo($now)) {
            return 'passed';
        }

        if ($start->lessThanOrEqualTo($now) && $end->greaterThan($now)) {
            return 'ongoing';
        }

        return 'upcoming';
    }

    protected function combineTodayWithTime(string $time): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('H:i:s', $time);

        return $this->nowInSchedulerTimezone()
            ->startOfDay()
            ->setTime((int) $parsed->format('H'), (int) $parsed->format('i'));
    }

    #[Computed]
    public function latestUploadPreviewUrl(): ?string
    {
        $upload = $this->latestUpload;

        if (! $upload || ! str_starts_with(strtolower(trim((string) $upload->mime_type)), 'image/')) {
            return null;
        }

        return route('timetable.preview', $upload);
    }

    protected function nowInSchedulerTimezone(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone', 'UTC'));
    }

    protected function formatTimeRangeValue(CarbonImmutable $time): string
    {
        return strtolower($time->format('g:i a'));
    }

    public function dayLabel(int $dayOfWeek): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$dayOfWeek] ?? 'Unknown';
    }

    protected function attachTodoToClass(Todo $todo): void
    {














        // Removed the entire method implementation
    }

    /**
     * @param  EloquentCollection<int, ScheduleSlot>  $slots
     * @return array<int, array{subject_key:string,course_code:?string,course_name:?string,label:string}>
     */
    protected function uniqueSubjectsFromSlots(EloquentCollection $slots): array
    {
        return $slots
            ->map(function (ScheduleSlot $slot): array {
                $parts = $this->resolveSubjectParts($slot);
                $label = $parts['code'] && $parts['name']
                    ? $parts['code'].' - '.$parts['name']
                    : (string) ($parts['code'] ?? $parts['name'] ?? $slot->subject);

                return [
                    'subject_key' => $this->subjectKeyForSlot($slot),
                    'course_code' => $parts['code'],
                    'course_name' => $parts['name'],
                    'label' => $label,
                ];
            })
            ->unique('subject_key')
            ->values()
            ->all();
    }

    protected function subjectKeyForSlot(ScheduleSlot $slot): string
    {
        $parts = $this->resolveSubjectParts($slot);

        return implode('|', array_map(
            fn (?string $value): string => $this->normalizeMatchText($value),
            [$parts['code'], $parts['name'], $slot->subject],
        ));
    }

    protected function nearestUpcomingSlotForSubject(EloquentCollection $slots, string $subjectKey): ?ScheduleSlot
    {
        $candidates = $slots
            ->filter(fn (ScheduleSlot $slot): bool => $this->subjectKeyForSlot($slot) === $subjectKey)
            ->values();

        return $this->nearestUpcomingSlotFromCollection($candidates);
    }

    protected function nearestUpcomingSlotFromCollection(EloquentCollection $candidates): ?ScheduleSlot
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $now = $this->nowInSchedulerTimezone();

        return $candidates
            ->map(function (ScheduleSlot $slot) use ($now): array {
                $daysAhead = ((int) $slot->day_of_week - (int) $now->dayOfWeek + 7) % 7;
                $start = CarbonImmutable::createFromFormat('H:i:s', $slot->starts_at, $now->timezone)
                    ->setDate($now->year, $now->month, $now->day)
                    ->addDays($daysAhead);

                if ($daysAhead === 0 && $start->lessThanOrEqualTo($now)) {
                    $start = $start->addWeek();
                }

                return [
                    'slot' => $slot,
                    'start' => $start,
                ];
            })
            ->sortBy('start')
            ->pluck('slot')
            ->first();
    }

    protected function matchTodoToSlot(Todo $todo, ScheduleSlot $slot): int
    {
        $todoText = $this->normalizeMatchText($todo->title.' '.($todo->description ?? ''));
        $slotText = $this->normalizeMatchText(implode(' ', array_filter([
            $slot->subject,
            $slot->course_code,
            $slot->course_name,
            $slot->faculty_name,
            $slot->room,
            $slot->block,
        ])));

        if ($todoText === '' || $slotText === '') {
            return 0;
        }

        $score = 0;

        if ($slot->course_code !== null) {
            $code = $this->normalizeMatchText($slot->course_code);

            if ($code !== '' && str_contains($todoText, $code)) {
                $score += 80;
            }
        }

        if ($slot->course_name !== null) {
            $courseName = $this->normalizeMatchText($slot->course_name);

            if ($courseName !== '' && str_contains($todoText, $courseName)) {
                $score += 60;
            }
        }

        if ($slot->subject !== null) {
            $subject = $this->normalizeMatchText($slot->subject);

            if ($subject !== '' && str_contains($todoText, $subject)) {
                $score += 40;
            }
        }

        $todoTokens = $this->tokenizeMatchText($todoText);
        $slotTokens = $this->tokenizeMatchText($slotText);
        $overlap = count(array_intersect($todoTokens, $slotTokens));

        $score += min(36, $overlap * 12);

        return $score;
    }

    protected function formatTodoMatch(Todo $todo, int $score): array
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'due_at' => $todo->due_at?->format('M j, g:i a'),
            'score' => $score,
        ];
    }

    protected function normalizeMatchText(?string $value): string
    {
        $text = strtolower(trim((string) $value));
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? $text;
        $tokens = array_filter(array_map([$this, 'normalizeMatchToken'], preg_split('/\s+/', trim($text)) ?: []));

        return implode(' ', $tokens);
    }

    protected function tokenizeMatchText(string $value): array
    {
        return array_values(array_filter(explode(' ', $value)));
    }

    protected function normalizeMatchToken(string $token): string
    {
        $token = trim(strtolower($token));

        if ($token === '') {
            return '';
        }

        if (strlen($token) > 4) {
            if (Str::endsWith($token, 'ies')) {
                $token = substr($token, 0, -3).'y';
            } elseif (Str::endsWith($token, 'es')) {
                $token = substr($token, 0, -2);
            } elseif (Str::endsWith($token, 's')) {
                $token = substr($token, 0, -1);
            }
        }

        return $token;
    }
}; ?>

<div
    @if (! $this->isTimetableReady && in_array((string) ($this->latestUpload?->status ?? ''), ['pending', 'processing'], true))
        wire:poll.3s
    @endif
>
    <x-scheduler.timetable-setup-screen
        :latestUpload="$this->latestUpload"
        :isTimetableReady="$this->isTimetableReady"
        :latestUploadPreviewUrl="$this->latestUploadPreviewUrl"
    />

    @if ($this->isTimetableReady)
        <section class="w-full space-y-5 md:space-y-6">
            <x-scheduler.dashboard-header />

            @if (auth()->user()->isRole('teacher'))
                <x-scheduler.teacher-roster :slots="$this->teacherScheduleSlots" />
            @endif

            <x-scheduler.slot-status
                :currentSlot="$this->currentSlot"
                :nextSlot="$this->nextSlot"
                :suggestedSlots="$this->suggestedSlots"
                :slotTodoMatches="$this->slotTodoMatches"
            />

            <x-scheduler.timeline-section :todayTimeline="$this->todayTimeline" />

            <x-scheduler.timetable-summary
                :latestUpload="$this->latestUpload"
                :latestUploadPreviewUrl="$this->latestUploadPreviewUrl"
            />

            <div class="overflow-hidden rounded-3xl border border-cyan-200/60 bg-gradient-to-br from-cyan-50 via-white to-emerald-50 p-5 shadow-sm dark:border-cyan-900/40 dark:from-cyan-950/20 dark:via-zinc-900 dark:to-emerald-950/20">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="lg">{{ __('Need to manage todos?') }}</flux:heading>
                        <flux:text>{{ __('Open the dedicated To-dos workspace for writing, prioritizing, and completing tasks.') }}</flux:text>
                    </div>

                    <flux:button variant="primary" icon="clipboard-document-check" :href="route('todos')">
                        {{ __('Open To-dos') }}
                    </flux:button>
                </div>
            </div>
        </section>
    @endif

    <div
        x-show="$wire.showTimetableModal"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="display: none;"
        @keydown.escape="$wire.set('showTimetableModal', false)"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900" @click.stop>
            <div class="space-y-4">
                <flux:heading>{{ __('Upload permanent timetable') }}</flux:heading>
                <flux:text>{{ __('Upload a new file to replace your current timetable and regenerate slots.') }}</flux:text>

                <form wire:submit="uploadTimetable" class="space-y-4">
                    <label for="timetable" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ __('Timetable file') }}</label>
                    <input
                        id="timetable"
                        wire:model="timetable"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:file:bg-zinc-200 dark:file:text-zinc-900"
                    />
                    @error('timetable')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Save timetable') }}</flux:button>
                        <button type="button" wire:click="$set('showTimetableModal', false)" class="rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>

                @if ($uploadQueued)
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('Queued for processing.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
