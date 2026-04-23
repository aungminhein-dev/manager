<?php

namespace App\Actions\Scheduler;

use App\Models\ScheduleSlot;
use App\Models\Todo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArrangeTodo
{
    public function __construct(
        public GeminiSchedulerClient $client,
        public PrioritizeTodo $prioritizeTodo,
        public TodoCategoryClassifier $classifier,
    ) {}

    public function execute(Todo $todo): void
    {
        $todo->loadMissing('user', 'scheduleSlot');

        $this->categorizeTodo($todo);
        $this->attachTodoToClass($todo);
        $this->prioritizeTodo->execute($todo);
    }

    protected function categorizeTodo(Todo $todo): void
    {
        $user = $todo->user;

        if ($user === null) {
            return;
        }

        $category = $this->classifier->categorize(
            (string) ($user->role ?? 'other'),
            $todo->title,
            $todo->description,
            true,
        );

        if ($category === null) {
            return;
        }

        $todo->forceFill([
            'category' => $category,
        ])->save();
    }

    protected function attachTodoToClass(Todo $todo): void
    {
        if ((string) config('services.gemini.api_key') === '') {
            return;
        }

        $user = $todo->user;

        if ($user === null) {
            return;
        }

        $slots = $user->scheduleSlots()
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        if ($slots->isEmpty()) {
            return;
        }

        $subjects = $this->uniqueSubjectsFromSlots($slots);

        if ($subjects === []) {
            return;
        }

        try {
            $subjectKey = $this->client->classifyTodoSubjectFromSubjects(
                (string) $user->role,
                $todo->title,
                $todo->description,
                $subjects,
            );
        } catch (\Throwable) {
            return;
        }

        $matchedSlot = null;

        if ($subjectKey !== null) {
            $subjectKey = trim($subjectKey);

            $matchedSlot = $slots
                ->filter(fn (ScheduleSlot $slot): bool => $this->subjectKeyForSlot($slot) === $subjectKey)
                ->values();

            if ($matchedSlot->isEmpty()) {
                $keyParts = array_values(array_filter(array_map('trim', explode('|', $subjectKey)), fn (string $part): bool => $part !== ''));

                if (count($keyParts) >= 2) {
                    $prefix = implode('|', array_slice($keyParts, 0, 2));

                    $matchedSlot = $slots
                        ->filter(function (ScheduleSlot $slot) use ($prefix): bool {
                            $slotKey = $this->subjectKeyForSlot($slot);

                            return $slotKey === $prefix || str_starts_with($slotKey, $prefix.'|');
                        })
                        ->values();
                }
            }

            $matchedSlot = $this->nearestUpcomingSlotFromCollection($matchedSlot);
        }

        if ($matchedSlot === null) {
            $best = $slots
                ->map(fn (ScheduleSlot $slot): array => [
                    'slot' => $slot,
                    'score' => $this->matchTodoToSlot($todo, $slot),
                ])
                ->sortByDesc('score')
                ->first();

            if (! is_array($best) || ($best['score'] ?? 0) < 18) {
                return;
            }

            $matchedSlot = $best['slot'];
        }

        if ($matchedSlot === null) {
            return;
        }

        $todo->forceFill([
            'schedule_slot_id' => $matchedSlot->id,
        ])->save();
    }

    /**
     * @param  EloquentCollection<int, ScheduleSlot>  $slots
     * @return array<int, array{subject_key:string,course_code:?string,course_name:?string,section:?string,assignment:?string,label:string}>
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
                    'section' => $parts['section'],
                    'assignment' => $parts['assignment'],
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
            [$parts['code'], $parts['name'], $parts['section'], $parts['assignment'], $slot->subject],
        ));
    }

    protected function nearestUpcomingSlotFromCollection(EloquentCollection $candidates): ?ScheduleSlot
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $now = CarbonImmutable::now(config('app.timezone', 'UTC'));

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
            $slot->section,
            $slot->assignment,
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

    protected function parseSubjectParts(string $subject): array
    {
        $text = trim($subject);

        if ($text === '') {
            return ['code' => null, 'name' => null, 'faculty' => null];
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
}
