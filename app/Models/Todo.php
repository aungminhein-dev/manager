<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;


#[Fillable([
    'user_id',
    'schedule_slot_id',
    'title',
    'description',
    'category',
    'due_at',
    'scheduled_for',
    'status',
    'role_score',
    'ai_score',
    'priority_score',
    'completed_at',
])]
class Todo extends Model
{
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }
}
