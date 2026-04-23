<?php

namespace App\Actions\Scheduler;

use App\Models\Todo;
use App\Models\User;
use Carbon\CarbonImmutable;

class PrioritizeTodo
{
    public function __construct(public GeminiSchedulerClient $client) {}

    public function execute(Todo $todo): void
    {
        $todo->loadMissing('user', 'scheduleSlot');

        $roleScore = $this->baselineScore($todo->user, $todo);

        $nextSlot = $todo->scheduleSlot && $todo->scheduleSlot->is_active
            ? $todo->scheduleSlot
            : $todo->user->scheduleSlots()
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->first();

        $aiScore = null;

        if ((string) config('services.gemini.api_key') !== '') {
            $aiScore = $this->client->suggestTodoScore(
                $todo->user->role,
                $todo->title,
                $todo->due_at?->toIso8601String(),
                $nextSlot === null
                    ? null
                    : [
                        'day' => $this->dayLabel((int) $nextSlot->day_of_week),
                        'starts_at' => $nextSlot->starts_at,
                        'ends_at' => $nextSlot->ends_at,
                        'subject' => $nextSlot->subject,
                    ],
            );
        }

        $todo->forceFill([
            'role_score' => $roleScore,
            'ai_score' => $aiScore,
            'priority_score' => $roleScore + ($aiScore ?? 0),
        ])->save();
    }

    public function reprioritizePending(User $user): void
    {
        $user->todos()
            ->where('status', 'pending')
            ->get()
            ->each(fn (Todo $todo) => $this->execute($todo));
    }

    protected function baselineScore(User $user, Todo $todo): int
    {
        $score = 10;
        $title = strtolower($todo->title);

        $roleBoost = match ($user->role) {
            'teacher' => str_contains($title, 'grade') || str_contains($title, 'lesson') ? 18 : 12,
            'corporate_worker' => str_contains($title, 'client') || str_contains($title, 'report') ? 18 : 12,
            default => str_contains($title, 'assignment') || str_contains($title, 'exam') ? 18 : 12,
        };

        $score += $roleBoost;

        if ($todo->due_at !== null) {
            $hoursRemaining = CarbonImmutable::now()->diffInHours(CarbonImmutable::parse($todo->due_at), false);

            if ($hoursRemaining <= 24) {
                $score += 22;
            } elseif ($hoursRemaining <= 72) {
                $score += 14;
            } elseif ($hoursRemaining <= 168) {
                $score += 8;
            }
        }

        return min(100, max(0, $score));
    }

    protected function dayLabel(int $dayOfWeek): string
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
}
