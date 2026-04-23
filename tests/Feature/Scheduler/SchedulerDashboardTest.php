<?php

namespace Tests\Feature\Scheduler;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchedulerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_scheduler_page(): void
    {
        $response = $this->get(route('scheduler'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_scheduler_page(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get(route('scheduler'));

        $response->assertStatus(200);
    }

    public function test_user_without_role_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create([
            'role' => null,
        ]);

        $response = $this->actingAs($user)->get(route('scheduler'));

        $response->assertRedirect(route('onboarding.role'));
    }

    public function test_user_can_add_todo_from_scheduler_component(): void
    {
        config()->set('services.gemini.api_key', '');

        $user = User::factory()->create([
            'role' => 'student',
        ]);

        Livewire::actingAs($user)
            ->test('pages::scheduler')
            ->set('todoTitle', 'Maths assignment')
            ->set('todoDescription', 'Finish chapter 4 problems')
            ->set('todoDueAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('addTodo');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Maths assignment',
            'status' => 'pending',
            'category' => null,
        ]);
    }
}
