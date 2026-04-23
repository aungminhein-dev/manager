<?php

namespace App\Jobs;

use App\Actions\Scheduler\ExtractSlotsFromTimetable;
use App\Actions\Scheduler\SyncScheduleSlots;
use App\Models\TimetableUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTimetableUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(public int $uploadId) {}

    /**
     * Execute the job.
     */
    public function handle(ExtractSlotsFromTimetable $extractor, SyncScheduleSlots $syncSlots): void
    {
        $upload = TimetableUpload::query()->with('user')->find($this->uploadId);

        if ($upload === null) {
            return;
        }

        try {
            $upload->forceFill([
                'status' => 'processing',
                'error_message' => null,
            ])->save();

            $slots = $extractor->execute($upload);

            $syncSlots->execute($upload, $slots);
        } catch (\Throwable $exception) {
            $isFinalAttempt = $this->attempts() >= $this->tries;

            $upload->forceFill([
                'status' => $isFinalAttempt ? 'failed' : 'pending',
                // Only show user-facing error when retries are exhausted.
                'error_message' => $isFinalAttempt ? $exception->getMessage() : null,
            ])->save();

            throw $exception;
        }
    }
}
