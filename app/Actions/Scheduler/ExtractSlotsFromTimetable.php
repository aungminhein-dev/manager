<?php

namespace App\Actions\Scheduler;

use App\Models\TimetableUpload;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

class ExtractSlotsFromTimetable
{
    public function __construct(public GeminiSchedulerClient $client) {}

    public function execute(TimetableUpload $upload): array
    {
        $binary = Storage::disk('local')->get($upload->file_path);
        $mimeType = $this->resolveMimeType($upload);
        $rawSlots = $this->client->parseTimetable($mimeType, $binary, $upload->user->role);

        $slots = [];

        foreach ($rawSlots as $rawSlot) {
            if (! is_array($rawSlot)) {
                continue;
            }

            $dayOfWeek = $this->normalizeDayOfWeek($rawSlot['day'] ?? null);
            $startsAt = $this->normalizeTime($rawSlot['starts_at'] ?? null);
            $endsAt = $this->normalizeTime($rawSlot['ends_at'] ?? null);
            $subject = $this->normalizeSubject($rawSlot);
            $courseCode = $this->normalizeCourseCode($rawSlot);
            $courseName = $this->nullableString($rawSlot['course_name'] ?? $rawSlot['name'] ?? null);
            $facultyName = $this->nullableString($rawSlot['faculty_name'] ?? $rawSlot['teacher'] ?? $rawSlot['faculty'] ?? null);
            $section = $this->nullableString($rawSlot['section'] ?? $rawSlot['class_section'] ?? $rawSlot['group'] ?? null);
            $assignment = $this->nullableString($rawSlot['assignment'] ?? $rawSlot['teaching_assignment'] ?? $rawSlot['class_name'] ?? null);
            ['room' => $room, 'block' => $block] = $this->normalizeRoomAndBlock(
                $rawSlot['room'] ?? null,
                $rawSlot['block'] ?? null,
            );

            if ($dayOfWeek === null || $startsAt === null || $endsAt === null || $subject === '') {
                continue;
            }

            if ($startsAt >= $endsAt) {
                continue;
            }

            $slots[] = [
                'day_of_week' => $dayOfWeek,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'subject_label' => $subject,
                'course_code' => $courseCode,
                'course_name' => $courseName,
                'faculty_name' => $facultyName,
                'section' => $section,
                'assignment' => $assignment,
                'room' => $room,
                'block' => $block,
                'source' => 'ai',
                'is_active' => true,
            ];
        }

        return $slots;
    }

    protected function normalizeDayOfWeek(mixed $day): ?int
    {
        if (is_numeric($day)) {
            $value = (int) $day;

            return $value >= 0 && $value <= 6 ? $value : null;
        }

        $key = strtolower(trim((string) $day));

        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $map[$key] ?? null;
    }

    protected function normalizeTime(mixed $time): ?string
    {
        $value = trim((string) $time);

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * Gemini may return subject, course_code, course_name, section, assignment, or a mix of them.
     * Prefer "CODE - Name" when both exist; otherwise keep whichever is present.
     *
     * @param  array<string, mixed>  $rawSlot
     */
    protected function normalizeSubject(array $rawSlot): string
    {
        $subject = trim((string) ($rawSlot['subject'] ?? ''));
        $courseCode = (string) ($this->normalizeCourseCode($rawSlot) ?? '');
        $courseName = trim((string) ($rawSlot['course_name'] ?? $rawSlot['name'] ?? ''));
        $section = trim((string) ($rawSlot['section'] ?? $rawSlot['class_section'] ?? $rawSlot['group'] ?? ''));
        $assignment = trim((string) ($rawSlot['assignment'] ?? $rawSlot['teaching_assignment'] ?? $rawSlot['class_name'] ?? ''));

        if ($courseCode !== '' && $courseName !== '') {
            return $courseCode.' - '.$courseName;
        }

        if ($subject !== '' && $courseCode !== '' && strcasecmp($subject, $courseCode) !== 0) {
            return $courseCode.' - '.$subject;
        }

        if ($subject !== '' && $courseName !== '' && strcasecmp($subject, $courseName) !== 0) {
            return $subject.' - '.$courseName;
        }

        if ($assignment !== '') {
            return $assignment;
        }

        if ($section !== '' && $courseName !== '') {
            return $section.' - '.$courseName;
        }

        return $subject !== '' ? $subject : ($courseCode !== '' ? $courseCode : $courseName);
    }

    /**
     * @param  array<string, mixed>  $rawSlot
     */
    protected function normalizeCourseCode(array $rawSlot): ?string
    {
        $courseCode = trim((string) ($rawSlot['course_code'] ?? $rawSlot['code'] ?? ''));

        if ($courseCode === '') {
            return null;
        }

        return strtoupper($courseCode);
    }

    /**
     * @return array{room:?string, block:?string}
     */
    protected function normalizeRoomAndBlock(mixed $rawRoom, mixed $rawBlock): array
    {
        $room = $this->nullableString($rawRoom);
        $block = $this->nullableString($rawBlock);

        if ($room !== null && preg_match('/^(?<room>.*?)[\s-]*block\s*(?<block>[a-z0-9]+)/i', $room, $matches) === 1) {
            $candidateRoom = trim((string) ($matches['room'] ?? ''), " -\t\n\r\0\x0B");
            $candidateBlock = trim((string) ($matches['block'] ?? ''));

            if ($candidateRoom !== '') {
                $room = $candidateRoom;
            }

            if ($block === null && $candidateBlock !== '') {
                $block = 'Block '.$candidateBlock;
            }
        }

        if ($block !== null && ! str_starts_with(strtolower($block), 'block')) {
            $block = 'Block '.$block;
        }

        return [
            'room' => $room,
            'block' => $block,
        ];
    }

    protected function resolveMimeType(TimetableUpload $upload): string
    {
        $stored = trim((string) $upload->mime_type);

        if ($stored !== '' && ! $this->isGenericMimeType($stored)) {
            return $stored;
        }

        $detected = trim((string) Storage::disk('local')->mimeType($upload->file_path));

        if ($detected !== '' && ! $this->isGenericMimeType($detected)) {
            return $detected;
        }

        return match (strtolower(pathinfo($upload->original_filename, PATHINFO_EXTENSION))) {
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
}
