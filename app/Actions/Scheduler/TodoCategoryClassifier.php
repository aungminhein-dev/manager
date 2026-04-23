<?php

namespace App\Actions\Scheduler;

use App\Models\Todo;
use Illuminate\Support\Str;

class TodoCategoryClassifier
{
    public function __construct(public GeminiSchedulerClient $client) {}

    public function matchExistingCategory(string $title, ?string $description = null): ?string
    {
        $profiles = $this->categoryProfiles();

        if ($profiles === []) {
            return null;
        }

        $text = $this->normalizeText($title.' '.($description ?? ''));

        if ($text === '') {
            return null;
        }

        $bestCategory = null;
        $bestScore = 0;

        foreach ($profiles as $category => $profile) {
            $score = $this->scoreProfile($text, $profile);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $category;
            }
        }

        return $bestCategory !== null && $bestScore >= 3 ? $bestCategory : null;
    }

    public function categorize(string $role, string $title, ?string $description = null, bool $allowAi = true): ?string
    {
        $knownCategory = $this->matchExistingCategory($title, $description);

        if ($knownCategory !== null) {
            return $knownCategory;
        }

        if (! $allowAi || (string) config('services.gemini.api_key') === '') {
            return null;
        }

        try {
            return $this->client->suggestTodoCategory(
                $role,
                $title,
                $description,
                $this->knownCategories(),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array{keywords: array<int, string>, count: int}>
     */
    protected function categoryProfiles(): array
    {
        $profiles = [];

        Todo::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->latest('id')
            ->limit(400)
            ->get(['category', 'title', 'description'])
            ->each(function (Todo $todo) use (&$profiles): void {
                $category = $this->normalizeCategoryLabel((string) $todo->category);

                if ($category === '') {
                    return;
                }

                $profiles[$category] ??= ['keywords' => [], 'count' => 0];
                $profiles[$category]['count']++;
                $profiles[$category]['keywords'] = array_merge(
                    $profiles[$category]['keywords'],
                    $this->tokenize($category.' '.$todo->title.' '.($todo->description ?? '')),
                );
            });

        foreach ($profiles as $category => $profile) {
            $profiles[$category]['keywords'] = array_values(array_unique(array_filter($profile['keywords'])));
        }

        return $profiles;
    }

    /**
     * @param  array{keywords: array<int, string>, count: int}  $profile
     */
    protected function scoreProfile(string $text, array $profile): int
    {
        $tokens = $this->tokenize($text);
        $keywords = $profile['keywords'];

        if ($keywords === []) {
            return 0;
        }

        $score = 0;

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($text, $keyword)) {
                $score += 3;
            }
        }

        $score += count(array_intersect($tokens, $keywords)) * 2;
        $score += min(4, (int) floor(($profile['count'] ?? 0) / 3));

        return $score;
    }

    /**
     * @return array<int, string>
     */
    protected function knownCategories(): array
    {
        return Todo::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn (mixed $category): string => $this->normalizeCategoryLabel((string) $category))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeCategoryLabel(string $label): string
    {
        $normalized = Str::of($label)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        return $normalized;
    }

    protected function normalizeText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }

    /**
     * @return array<int, string>
     */
    protected function tokenize(string $text): array
    {
        $tokens = explode(' ', $this->normalizeText($text));

        return array_values(array_filter(array_map([$this, 'normalizeToken'], $tokens)));
    }

    protected function normalizeToken(string $token): string
    {
        $token = trim(mb_strtolower($token));

        if ($token === '') {
            return '';
        }

        if (strlen($token) > 4) {
            if (Str::endsWith($token, 'ies')) {
                $token = substr($token, 0, -3).'y';
            } elseif (Str::endsWith($token, 'es')) {
                $token = substr($token, 0, -2);
            } elseif (Str::endsWith($token, 's')) {
                $token = substr($token, 0, -1);
            }
        }

        return $token;
    }
}