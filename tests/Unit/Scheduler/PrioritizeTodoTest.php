<?php

namespace Tests\Unit\Scheduler;

use App\Actions\Scheduler\PrioritizeTodo;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrioritizeTodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_scoring_prioritizes_student_assignment_keywords(): void
    {
        config()->set('services.gemini.api_key', '');

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $todo = Todo::query()->create([
            'user_id' => $student->id,
            'title' => 'Maths assignment',
            'status' => 'pending',
            'due_at' => now()->addHours(10),
        ]);

        app(PrioritizeTodo::class)->execute($todo);

        $todo->refresh();

        $this->assertGreaterThan(0, $todo->role_score);
        $this->assertSame($todo->role_score, $todo->priority_score);
    }

    public function test_teacher_keyword_is_scored_higher_than_generic_task(): void
    {
        config()->set('services.gemini.api_key', '');

        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $grading = Todo::query()->create([
            'user_id' => $teacher->id,
            'title' => 'Grade exam papers',
            'status' => 'pending',
        ]);

        $generic = Todo::query()->create([
            'user_id' => $teacher->id,
            'title' => 'Organize files',
            'status' => 'pending',
        ]);

        $prioritizer = app(PrioritizeTodo::class);

        $prioritizer->execute($grading);
        $prioritizer->execute($generic);

        $grading->refresh();
        $generic->refresh();

        $this->assertGreaterThan($generic->role_score, $grading->role_score);
    }
}
