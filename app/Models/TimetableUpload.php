<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'original_filename',
    'file_path',
    'mime_type',
    'status',
    'error_message',
    'parsed_payload',
    'parsed_at',
])]
class TimetableUpload extends Model
{
    protected function casts(): array
    {
        return [
            'parsed_payload' => 'array',
            'parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    protected function isProcessed(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status === 'completed');
    }
}
