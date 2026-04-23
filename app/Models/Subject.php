<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'subject_key',
    'course_code',
    'course_name',
    'faculty_name',
    'section',
    'assignment',
    'label',
])]
class Subject extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
