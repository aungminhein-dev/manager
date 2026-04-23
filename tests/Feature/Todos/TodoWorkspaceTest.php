<?php

namespace Tests\Feature\Todos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodoWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_todo_workspace(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get(route('todos'));

        $response->assertStatus(200);
    }

    public function test_user_can_add_todo_from_todo_workspace(): void
    {
        config()->set('services.gemini.api_key', '');

        $user = User::factory()->create([
            'role' => 'student',
        ]);

        Livewire::actingAs($user)
            ->test('pages::to-dos')
            ->set('todoTitle', 'Pay internet bill')
            ->set('todoDescription', 'Monthly payment before Friday')
            ->set('todoDueAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('addTodo');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Pay internet bill',
            'status' => 'pending',
            'category' => null,
        ]);
    }
}
