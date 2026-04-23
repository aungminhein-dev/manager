<?php

namespace App\Actions\Scheduler;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiSchedulerClient
{
    public function suggestTodoCategory(string $role, string $title, ?string $description, array $existingCategories = []): ?string
    {
        $categoryHints = $existingCategories === []
            ? 'No existing categories yet.'
            : "Existing categories already in use:\n".$this->formatCategoryOptions($existingCategories);

        $prompt = <<<PROMPT
Suggest a concise category name for this todo.

Role: {$role}
Todo title: {$title}
Todo description: {$description}

{$categoryHints}

Return strict JSON only in one of these shapes:
{"category":"category-name"}
{"category":null}

Rules:
1) Prefer an existing category if one clearly fits.
2) Otherwise create a short, human-readable category name of 1-3 words.
3) Return null if the todo is too ambiguous to categorize.
4) Do not include extra keys or explanation.
PROMPT;

        $data = $this->requestJson([
            [
                'text' => $prompt,
            ],
        ]);

        $category = data_get($data, 'category');

        return is_string($category) && trim($category) !== '' ? $this->normalizeCategoryLabel($category) : null;
    }

    public function classifyTodoSubjectFromSubjects(string $role, string $title, ?string $description, array $subjects): ?string
    {
        if ($subjects === []) {
            return null;
        }

        $prompt = <<<PROMPT
Determine whether this todo belongs to one of the student's school subjects.

Role: {$role}
Todo title: {$title}
Todo description: {$description}

Student subjects:
{$this->formatSubjectOptions($subjects)}

Return strict JSON only in one of these shapes:
{"subject_key":"exact-key-from-list"}
{"subject_key":null}

Rules:
1) Use only subject_key values from the provided list.
2) Return null for personal/non-school tasks.
3) If ambiguous, return null.
4) Do not include extra keys or explanation.
PROMPT;

        $data = $this->requestJson([
            [
                'text' => $prompt,
            ],
        ]);

        $subjectKey = data_get($data, 'subject_key');

        return is_string($subjectKey) && $subjectKey !== '' ? $subjectKey : null;
    }

    public function classifyTodoSubject(string $role, string $title, ?string $description, array $slots): ?int
    {
        if ($slots === []) {
            return null;
        }

        $prompt = <<<PROMPT
Match this student todo to exactly one timetable class, or null if none fits.

Role: {$role}
Todo title: {$title}
Todo description: {$description}

Available classes:
{$this->formatClassOptions($slots)}

Return strict JSON only in this exact shape:
{"schedule_slot_id":123}
Or use null when there is no clear match:
{"schedule_slot_id":null}

Rules:
1) Use only the available classes above.
2) Prefer the class whose subject, course name, or course code best matches the todo text.
3) Do not add any extra keys or commentary.
PROMPT;

        $data = $this->requestJson([
            [
                'text' => $prompt,
            ],
        ]);

        $slotId = data_get($data, 'schedule_slot_id');

        return is_numeric($slotId) ? (int) $slotId : null;
    }

    public function parseTimetable(string $mimeType, string $binaryContent, string $role): array
    {
        $prompt = <<<PROMPT
Extract timetable slots from this timetable image for a {$role}.

Return strict JSON ONLY in this exact shape:
{"slots":[{"day":"Monday","starts_at":"09:00","ends_at":"09:50","course_code":"CSCR1505","course_name":"Introduction to Data Structures","faculty_name":"Dr. Smita Tiwari","subject":"CSCR1505 - Introduction to Data Structures","room":"219A","block":"Block 3"}]}

Rules:
1) Day must be weekday name (Monday..Sunday) or integer 0-6 where 0 is Sunday.
2) starts_at and ends_at must be 24h HH:MM.
3) Use the timetable grid cells only (ignore lunch/free blocks as classes).
4) If a slot cell contains only course code, map course_name from the Subjects legend when present.
5) Map faculty_name from the Teachers legend when possible.
6) If course_name is unavailable, leave course_name as empty string but still provide course_code.
7) section must be the class/cohort/section label taught in that slot when visible, otherwise null.
8) assignment must be the teacher-facing teaching assignment or class label shown in the slot when visible, otherwise null.
9) room must be room number/label only (example: "219A"), without "Block" text.
10) block must be block label only (example: "Block 3").
11) subject should be a concise display fallback: "CODE - NAME" when both exist, otherwise whichever exists.
12) Do not include markdown, comments, trailing text, or extra keys.
PROMPT;

        $data = $this->requestJson([
            [
                'text' => $prompt,
            ],
            [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => base64_encode($binaryContent),
                ],
            ],
        ]);

        return $data['slots'] ?? [];
    }

    public function suggestTodoScore(string $role, string $title, ?string $dueAt, ?array $nextSlot): ?int
    {
        $slotSummary = $nextSlot === null
            ? 'No upcoming slot'
            : sprintf(
                '%s %s-%s (%s)',
                $nextSlot['day'] ?? 'Unknown day',
                $nextSlot['starts_at'] ?? '--:--',
                $nextSlot['ends_at'] ?? '--:--',
                $nextSlot['subject'] ?? 'No subject',
            );

        $prompt = <<<PROMPT
You score urgency for a {$role} task.
Task: {$title}
Due: {$dueAt}
Next Slot: {$slotSummary}

Return strict JSON only: {"score": number}
Score range must be 0 to 40.
PROMPT;

        $data = $this->requestJson([
            [
                'text' => $prompt,
            ],
        ]);

        if (! isset($data['score']) || ! is_numeric($data['score'])) {
            return null;
        }

        return max(0, min(40, (int) $data['score']));
    }

    protected function requestJson(array $parts): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $primaryModel = trim((string) config('services.gemini.model'));
        $fallbackModels = config('services.gemini.fallback_models', []);
        $modelCandidates = array_values(array_unique(array_filter([
            $primaryModel,
            ...is_array($fallbackModels) ? $fallbackModels : [],
        ], fn (mixed $value): bool => is_string($value) && trim($value) !== '')));
        $baseUrl = rtrim((string) config('services.gemini.base_url'), '/');
        $timeout = (int) config('services.gemini.timeout', 90);
        $attempts = max(1, (int) config('services.gemini.retry_attempts', 5));
        $initialBackoffMs = max(100, (int) config('services.gemini.retry_initial_backoff_ms', 1000));
        $maxBackoffMs = max($initialBackoffMs, (int) config('services.gemini.retry_max_backoff_ms', 10000));

        if ($apiKey === '') {
            throw new RuntimeException('Missing Gemini API key. Set GEMINI_API_KEY.');
        }

        if ($modelCandidates === []) {
            throw new RuntimeException('Missing Gemini model. Set GEMINI_MODEL or GEMINI_FALLBACK_MODELS.');
        }

        $lastTransientResponse = null;
        $lastConnectionException = null;
        $lastModel = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            foreach ($modelCandidates as $model) {
                try {
                    $response = Http::connectTimeout(20)
                        ->timeout($timeout)
                        ->asJson()
                        ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => $parts,
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

                        if ($text === '') {
                            throw new RuntimeException('Gemini returned an empty response.');
                        }

                        $json = $this->decodeJsonBlock($text);

                        if (! is_array($json)) {
                            throw new RuntimeException('Gemini response was not valid JSON.');
                        }

                        return $json;
                    }

                    if (! $this->shouldRetryStatus($response->status())) {
                        throw new RuntimeException($this->buildFailureMessage($response, $model));
                    }

                    $lastTransientResponse = $response;
                    $lastModel = $model;
                } catch (ConnectionException $exception) {
                    $lastConnectionException = $exception;
                    $lastModel = $model;
                }
            }

            if ($attempt < $attempts) {
                usleep($this->resolveBackoffMs($lastTransientResponse, $attempt, $initialBackoffMs, $maxBackoffMs) * 1000);
            }
        }

        if ($lastTransientResponse !== null) {
            throw new RuntimeException($this->buildFailureMessage($lastTransientResponse, $lastModel));
        }

        if ($lastConnectionException !== null) {
            throw new RuntimeException('Gemini request failed after retries due to a network error.', previous: $lastConnectionException);
        }

        throw new RuntimeException('Gemini request failed unexpectedly.');
    }

    protected function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    protected function resolveBackoffMs(?Response $response, int $attempt, int $initialBackoffMs, int $maxBackoffMs): int
    {
        $retryAfter = $this->retryAfterMs($response);

        if ($retryAfter !== null) {
            return min($retryAfter, $maxBackoffMs);
        }

        $exponential = (int) ($initialBackoffMs * (2 ** max(0, $attempt - 1)));

        return min($exponential + random_int(0, 250), $maxBackoffMs);
    }

    protected function retryAfterMs(?Response $response): ?int
    {
        if ($response === null) {
            return null;
        }

        $value = trim((string) $response->header('Retry-After'));

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value * 1000);
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return max(0, ($timestamp - time()) * 1000);
    }

    protected function buildFailureMessage(Response $response, ?string $model = null): string
    {
        $message = (string) data_get($response->json(), 'error.message', '');
        $prefix = $model === null
            ? 'Gemini request failed with status '.$response->status()
            : 'Gemini request failed with status '.$response->status().' using model '.$model;

        if ($message === '') {
            return $prefix;
        }

        return $prefix.': '.$message;
    }

    protected function formatCategoryOptions(array $categories): string
    {
        return collect($categories)
            ->map(function (mixed $label, mixed $key): string {
                $categoryLabel = is_string($label) ? $label : (string) $key;

                return sprintf('- %s', $categoryLabel);
            })
            ->implode("\n");
    }

    protected function normalizeCategoryLabel(string $label): string
    {
        $normalized = strtolower(trim($label));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }

    protected function decodeJsonBlock(string $text): ?array
    {
        $trimmed = trim($text);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```json|^```|```$/m', '', $trimmed) ?? $trimmed;
        }

        $decoded = json_decode(trim($trimmed), true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function formatClassOptions(array $slots): string
    {
        $lines = [];

        foreach ($slots as $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $lines[] = sprintf(
                '- id: %s | %s %s-%s | %s | %s | %s | %s | %s | %s',
                $slot['id'] ?? 'null',
                $slot['day'] ?? 'Unknown',
                $slot['starts_at'] ?? '--:--',
                $slot['ends_at'] ?? '--:--',
                $slot['subject'] ?? 'No subject',
                $slot['course_code'] ?? '',
                $slot['course_name'] ?? '',
                $slot['section'] ?? '',
                $slot['assignment'] ?? '',
                trim(implode(' ', array_filter([
                    $slot['room'] ?? null,
                    $slot['block'] ?? null,
                ])))
            );
        }

        return implode("\n", $lines);
    }

    protected function formatSubjectOptions(array $subjects): string
    {
        $lines = [];

        foreach ($subjects as $subject) {
            if (! is_array($subject)) {
                continue;
            }

            $lines[] = sprintf(
                '- subject_key: %s | code: %s | name: %s | section: %s | assignment: %s | label: %s',
                $subject['subject_key'] ?? 'null',
                $subject['course_code'] ?? '',
                $subject['course_name'] ?? '',
                $subject['section'] ?? '',
                $subject['assignment'] ?? '',
                $subject['label'] ?? ''
            );
        }

        return implode("\n", $lines);
    }
}
