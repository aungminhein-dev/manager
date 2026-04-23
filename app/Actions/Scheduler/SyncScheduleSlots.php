<?php

namespace App\Actions\Scheduler;

use App\Models\Subject;
use App\Models\TimetableUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncScheduleSlots
{
    public function execute(TimetableUpload $upload, array $slots): void
    {
        DB::transaction(function () use ($upload, $slots): void {
            $upload->user->scheduleSlots()->update(['is_active' => false]);

            $subjectRecords = [];

            foreach ($slots as $slot) {
                $subjectData = $this->subjectPayloadForSlot($slot);

                $subject = Subject::query()->updateOrCreate(
                    [
                        'user_id' => $upload->user_id,
                        'subject_key' => $subjectData['subject_key'],
                    ],
                    [
                        'course_code' => $subjectData['course_code'],
                        'course_name' => $subjectData['course_name'],
                        'faculty_name' => $subjectData['faculty_name'],
                        'section' => $subjectData['section'],
                        'assignment' => $subjectData['assignment'],
                        'label' => $subjectData['label'],
                    ],
                );

                $upload->scheduleSlots()->create([
                    'day_of_week' => $slot['day_of_week'],
                    'starts_at' => $slot['starts_at'],
                    'ends_at' => $slot['ends_at'],
                    'room' => $slot['room'] ?? null,
                    'block' => $slot['block'] ?? null,
                    'source' => $slot['source'] ?? 'ai',
                    'subject_id' => $subject->id,
                    'user_id' => $upload->user_id,
                    'is_active' => (bool) ($slot['is_active'] ?? true),
                ]);

                $subjectRecords[$subject->id] = [
                    'id' => $subject->id,
                    'subject_key' => $subject->subject_key,
                    'course_code' => $subject->course_code,
                    'course_name' => $subject->course_name,
                    'faculty_name' => $subject->faculty_name,
                    'section' => $subject->section,
                    'assignment' => $subject->assignment,
                    'label' => $subject->label,
                ];
            }

            $upload->forceFill([
                'status' => 'completed',
                'parsed_at' => now(),
                'parsed_payload' => [
                    'subjects' => array_values($subjectRecords),
                    'slots' => $slots,
                ],
                'error_message' => null,
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $slot
     * @return array{subject_key:string,course_code:?string,course_name:?string,faculty_name:?string,label:string}
     * @return array{subject_key:string,course_code:?string,course_name:?string,faculty_name:?string,section:?string,assignment:?string,label:string}
     */
    protected function subjectPayloadForSlot(array $slot): array
    {
        $courseCode = $this->nullableString($slot['course_code'] ?? null);
        $courseName = $this->nullableString($slot['course_name'] ?? null);
        $facultyName = $this->nullableString($slot['faculty_name'] ?? null);
        $section = $this->nullableString($slot['section'] ?? null);
        $assignment = $this->nullableString($slot['assignment'] ?? null);
        $label = $this->nullableString($slot['subject_label'] ?? null)
            ?? $this->buildLabel($courseCode, $courseName)
            ?? 'Unknown Subject';

        $subjectKey = implode('|', array_map(
            fn (?string $value): string => Str::lower(trim((string) ($value ?? ''))),
            [$courseCode, $courseName, $facultyName, $section, $assignment, $label],
        ));

        return [
            'subject_key' => $subjectKey,
            'course_code' => $courseCode,
            'course_name' => $courseName,
            'faculty_name' => $facultyName,
            'section' => $section,
            'assignment' => $assignment,
            'label' => $label,
        ];
    }

    protected function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    protected function buildLabel(?string $courseCode, ?string $courseName): ?string
    {
        if ($courseCode !== null && $courseName !== null) {
            return $courseCode.' - '.$courseName;
        }

        return $courseCode ?? $courseName;
    }
}
