<?php

namespace Tests\Unit\Scheduler;

use App\Actions\Scheduler\TodoCategoryClassifier;
use Tests\TestCase;

class TodoCategoryClassifierTest extends TestCase
{
    public function test_school_keywords_are_categorized_as_school(): void
    {
        $classifier = app(TodoCategoryClassifier::class);

        $category = $classifier->guess('student', 'Finish chemistry assignment', 'Study chapter 3 tonight');

        $this->assertSame('school', $category);
    }

    public function test_finance_keywords_are_categorized_as_finance(): void
    {
        $classifier = app(TodoCategoryClassifier::class);

        $category = $classifier->guess('corporate_worker', 'Pay internet bill', null);

        $this->assertSame('finance', $category);
    }
}