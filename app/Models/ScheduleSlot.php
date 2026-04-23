<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'timetable_upload_id',
    'subject_id',
    'day_of_week',
    'starts_at',
    'ends_at',
    'room',
    'block',
    'is_active',
    'source',
])]
class ScheduleSlot extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timetableUpload(): BelongsTo
    {
        return $this->belongsTo(TimetableUpload::class);
    }

    public function subjectRef(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function getCourseCodeAttribute(?string $value): ?string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->course_code;
    }

    public function getCourseNameAttribute(?string $value): ?string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->course_name;
    }

    public function getFacultyNameAttribute(?string $value): ?string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->faculty_name;
    }

    public function getSectionAttribute(?string $value): ?string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->section;
    }

    public function getAssignmentAttribute(?string $value): ?string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->assignment;
    }

    public function getSubjectAttribute(?string $value): string
    {
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        return $this->subjectRef?->label ?? '';
    }
}
