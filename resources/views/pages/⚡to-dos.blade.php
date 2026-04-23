<?php

use App\Actions\Scheduler\PrioritizeTodo;
use App\Actions\Scheduler\TodoCategoryClassifier;
use App\Jobs\ProcessTodoArrangement;
use App\Models\Todo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('To-dos')] class extends Component {
    use AuthorizesRequests;

    public string $todoTitle = '';
    public string $todoDescription = '';
    public string $todoDueAt = '';

    public function addTodo(): void
    {
        $validated = $this->validate([
            'todoTitle' => ['required', 'string', 'max:255'],
            'todoDescription' => ['nullable', 'string', 'max:2000'],
            'todoDueAt' => ['nullable', 'date'],
        ]);

        $description = $validated['todoDescription'] === '' ? null : $validated['todoDescription'];
        $category = app(TodoCategoryClassifier::class)->matchExistingCategory(
            $validated['todoTitle'],
            $description,
        );

        $todo = Todo::query()->create([
            'user_id' => Auth::id(),
            'schedule_slot_id' => null,
            'title' => $validated['todoTitle'],
            'description' => $description,
            'category' => $category,
            'due_at' => $validated['todoDueAt'] === '' ? null : CarbonImmutable::parse($validated['todoDueAt']),
            'scheduled_for' => CarbonImmutable::tomorrow()->setTime(9, 0),
            'status' => 'pending',
            'role_score' => 0,
            'ai_score' => null,
            'priority_score' => 0,
        ]);

        dispatch(new ProcessTodoArrangement($todo->id));

        $this->reset('todoTitle', 'todoDescription', 'todoDueAt');
        $this->dispatch('todo-created');
    }

    public function completeTodo(int $todoId): void
    {
        $todo = Auth::user()->todos()->whereKey($todoId)->where('status', 'pending')->firstOrFail();

        $todo->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();
    }

    public function carryToTomorrow(int $todoId): void
    {
        $todo = Auth::user()->todos()->whereKey($todoId)->where('status', 'pending')->firstOrFail();

        $todo->forceFill([
            'scheduled_for' => CarbonImmutable::tomorrow()->setTime(9, 0),
        ])->save();

        app(PrioritizeTodo::class)->execute($todo);
    }

    #[Computed]
    public function pendingTodos(): EloquentCollection
    {
        return Auth::user()->todos()
            ->where('status', 'pending')
            ->orderByDesc('priority_score')
            ->orderBy('due_at')
            ->get();
    }

    #[Computed]
    public function todoSidebarSummary(): array
    {
        $pendingTodos = $this->pendingTodos;
        $now = CarbonImmutable::now(config('app.timezone', 'UTC'));

        $dueToday = $pendingTodos->filter(fn (Todo $todo): bool => $todo->due_at !== null && $todo->due_at->isSameDay($now))->count();
        $dueSoon = $pendingTodos->filter(fn (Todo $todo): bool => $todo->due_at !== null && $todo->due_at->lessThanOrEqualTo($now->copy()->addDays(2)))->count();
        $overdue = $pendingTodos->filter(fn (Todo $todo): bool => $todo->due_at !== null && $todo->due_at->lessThan($now))->count();
        $completedThisWeek = Auth::user()->todos()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $now->copy()->startOfWeek())
            ->count();

        $totalCompleted = Auth::user()->todos()->where('status', 'completed')->count();
        $completionRate = $pendingTodos->count() + $totalCompleted > 0
            ? (int) round(($totalCompleted / ($pendingTodos->count() + $totalCompleted)) * 100)
            : 0;

        return [
            'pending' => $pendingTodos->count(),
            'due_today' => $dueToday,
            'due_soon' => $dueSoon,
            'overdue' => $overdue,
            'completed_this_week' => $completedThisWeek,
            'completion_rate' => $completionRate,
        ];
    }

    #[Computed]
    public function todoCategoryBreakdown(): array
    {
        $pendingTodos = $this->pendingTodos;
        $total = max(1, $pendingTodos->count());

        return $pendingTodos
            ->groupBy(fn (Todo $todo): string => $this->normalizeTodoCategory((string) ($todo->category ?? 'uncategorized')))
            ->map(function (EloquentCollection $group, string $category) use ($total): array {
                return [
                    'key' => $category,
                    'label' => $this->displayTodoCategory($category),
                    'count' => $group->count(),
                    'percentage' => (int) round(($group->count() / $total) * 100),
                    'priority' => (int) round($group->avg('priority_score') ?? 0),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    #[Computed]
    public function focusTodos(): Collection
    {
        return $this->pendingTodos
            ->take(5)
            ->values();
    }

    protected function normalizeTodoCategory(string $category): string
    {
        $category = trim(mb_strtolower($category));

        return $category !== '' ? $category : 'uncategorized';
    }

    protected function displayTodoCategory(string $category): string
    {
        return $category === 'uncategorized' ? __('Uncategorized') : Str::headline($category);
    }
}; ?>

<section class="w-full space-y-6">
    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-cyan-50/40 to-emerald-50/30 p-6 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-cyan-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-300">{{ __('To-dos') }}</p>
                <flux:heading size="xl">{{ __('Focused task workspace') }}</flux:heading>
                <flux:text class="max-w-2xl">{{ __('Write tasks quickly, let the background job sort them into categories, and keep the next best action visible at all times.') }}</flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:badge color="zinc" size="sm">{{ $this->todoSidebarSummary['pending'] }} {{ __('open') }}</flux:badge>
                <flux:badge color="amber" size="sm">{{ $this->todoSidebarSummary['due_today'] }} {{ __('due today') }}</flux:badge>
                <flux:badge color="rose" size="sm">{{ $this->todoSidebarSummary['overdue'] }} {{ __('overdue') }}</flux:badge>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.85fr)] xl:items-start">
        <x-scheduler.todo-section :pendingTodos="$this->pendingTodos" />

        <x-scheduler.todo-sidebar
            :summary="$this->todoSidebarSummary"
            :categoryBreakdown="$this->todoCategoryBreakdown"
            :focusTodos="$this->focusTodos"
        />
    </div>
</section>
