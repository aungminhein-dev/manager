<?php

namespace App\Jobs;

use App\Actions\Scheduler\ArrangeTodo;
use App\Models\Todo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTodoArrangement implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $todoId) {}

    public function handle(ArrangeTodo $arranger): void
    {
        $todo = Todo::query()->with(['user', 'scheduleSlot'])->find($this->todoId);

        if ($todo === null) {
            return;
        }

        $arranger->execute($todo);
    }
}
