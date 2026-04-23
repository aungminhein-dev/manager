@props([
    'pendingTodos' => [],
])

@php
    $pendingCount = count($pendingTodos);
@endphp

<div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
    <section
        id="todo-form"
        x-data="{
            stage: 'idle',
            timer: null,
            setStage(value) {
                this.stage = value;
            },
        }"
        x-on:todo-created.window="stage = 'created'; clearTimeout(timer); timer = setTimeout(() => stage = 'arranging', 900)"
        class="scroll-mt-24 overflow-hidden rounded-3xl border border-sky-200/70 bg-gradient-to-br from-white via-sky-50/80 to-cyan-100/60 p-5 shadow-sm dark:border-sky-900/40 dark:from-zinc-900 dark:via-zinc-900 dark:to-cyan-950/20"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Quick add') }}</flux:heading>
                <flux:text class="max-w-xl">
                    {{ __('Add a task in one step. It appears immediately, then AI arranges it in the background.') }}
                </flux:text>
            </div>

            @if ($pendingCount > 0)
                <flux:badge color="zinc" size="sm">
                    {{ $pendingCount }} {{ $pendingCount === 1 ? __('task') : __('tasks') }}
                </flux:badge>
            @endif
        </div>

        <div class="mt-4 rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm backdrop-blur dark:border-zinc-700/60 dark:bg-zinc-950/70">
            <div class="flex items-start gap-3">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">
                    <flux:icon icon="sparkles" class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white" x-cloak>
                        <span x-show="stage === 'idle'">{{ __('Your task is ready to be added.') }}</span>
                        <span x-show="stage === 'created'">{{ __('Created.')}}</span>
                        <span x-show="stage === 'arranging'">{{ __('AI is arranging for you.') }}</span>
                    </p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400" x-cloak>
                        <span x-show="stage === 'idle'">{{ __('Save quickly, then we sort priority and category automatically.') }}</span>
                        <span x-show="stage === 'created'">{{ __('The task is saved. Matching and categorizing start now.') }}</span>
                        <span x-show="stage === 'arranging'">{{ __('You can keep working while the AI finishes.') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="addTodo" class="mt-5 space-y-4">
            <div class="grid gap-4">
                <flux:input wire:model="todoTitle" :label="__('Task title')" type="text" required placeholder="{{ __('Example: Finish Python assignment') }}" />
                <flux:textarea wire:model="todoDescription" :label="__('Description')" placeholder="{{ __('Add details, reminders, or subject context') }}" />
                <flux:input wire:model="todoDueAt" :label="__('Due at')" type="datetime-local" />
            </div>

            <div class="flex flex-col gap-3 border-t border-sky-200/60 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-sky-900/40">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Fast save, then automatic planning.') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('The task becomes visible right away while AI organizes the score in the background.') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <flux:button
                        variant="primary"
                        type="submit"
                        class="h-10 shadow-sm"
                        wire:loading.attr="disabled"
                        wire:target="addTodo"
                    >
                        <span wire:loading.remove wire:target="addTodo">{{ __('Add task') }}</span>
                        <span wire:loading wire:target="addTodo" class="inline-flex items-center gap-2">
                            <flux:icon icon="sparkles" class="size-4 animate-pulse" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>

                    <x-action-message on="todo-created" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        {{ __('Created.') }}
                    </x-action-message>
                </div>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Priority list') }}</flux:heading>
                <flux:text>{{ __('Tasks are ordered by the score that AI calculates for you and tagged with smart categories.') }}</flux:text>
            </div>

            @if ($pendingCount > 0)
                <flux:badge color="amber" size="sm">
                    {{ $pendingCount }} {{ $pendingCount === 1 ? __('task') : __('tasks') }}
                </flux:badge>
            @endif
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($pendingTodos as $todo)
                <article wire:key="todo-{{ $todo->id }}" class="group overflow-hidden rounded-2xl border border-zinc-200 bg-gradient-to-br from-white to-zinc-50 p-4 transition hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-950/40 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <h4 class="truncate text-base font-semibold leading-tight text-zinc-950 dark:text-white">{{ $todo->title }}</h4>
                                @if ($todo->due_at)
                                    <span class="rounded-full bg-rose-500/10 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                        {{ __('Due') }}
                                    </span>
                                @endif
                                @if ($todo->category)
                                    <flux:badge color="sky" size="sm" class="py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                        {{ \Illuminate\Support\Str::headline($todo->category) }}
                                    </flux:badge>
                                @endif
                            </div>

                            @if ($todo->description)
                                <p class="line-clamp-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $todo->description }}</p>
                            @endif

                            @if ($todo->due_at)
                                <div class="flex items-center gap-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">
                                    <flux:icon icon="clock" class="size-3.5" />
                                    <span>{{ strtolower($todo->due_at->format('M j, g:i a')) }}</span>
                                </div>
                            @endif
                        </div>

                        <flux:badge color="amber" class="shrink-0 flex items-center gap-1 py-1.5 shadow-sm">
                            <span class="sr-only">{{ __('Priority score:') }}</span>
                            <flux:icon icon="star" class="size-3.5" />
                            <span>{{ $todo->priority_score }}</span>
                        </flux:badge>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <flux:button variant="ghost" size="sm" class="h-9" icon="calendar-days" wire:click="carryToTomorrow({{ $todo->id }})">
                            {{ __('To tomorrow') }}
                        </flux:button>
                        <flux:button variant="primary" size="sm" class="ml-auto h-9" icon="check" wire:click="completeTodo({{ $todo->id }})">
                            {{ __('Done') }}
                        </flux:button>
                    </div>
                </article>
            @empty
                <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 bg-zinc-50/50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-800/20">
                    <div class="mb-4 flex items-center justify-center rounded-full bg-emerald-100 p-3 ring-8 ring-emerald-50 dark:bg-emerald-900/30 dark:ring-emerald-900/10">
                        <flux:icon icon="clipboard-document-check" class="size-7 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <flux:heading size="md" class="mb-1 text-zinc-900 dark:text-zinc-100">{{ __('All caught up!') }}</flux:heading>
                    <flux:text class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ __('You have no pending tasks right now. Add one and it will appear here with an automatic priority score and category.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </section>
</div>
