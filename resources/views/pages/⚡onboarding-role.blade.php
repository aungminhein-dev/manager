<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Choose your role')] class extends Component {
    public string $role = 'student';

    public function continueToScheduler(): void
    {
        $validated = $this->validate([
            'role' => ['required', 'in:student,teacher,corporate_worker'],
        ]);

        Auth::user()->update([
            'role' => $validated['role'],
        ]);

        $this->redirectRoute('scheduler');
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6 rounded-3xl border border-zinc-200 bg-gradient-to-b from-white to-zinc-50 p-5 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-950 md:p-8">
    <div class="space-y-2 text-center">
        <flux:heading size="xl">{{ __('Tell us who you are') }}</flux:heading>
        <flux:text>{{ __('We use your role to prioritize tasks and schedule suggestions.') }}</flux:text>
    </div>

    <form wire:submit="continueToScheduler" class="space-y-4">
        <label class="block cursor-pointer" wire:click="$set('role', 'student')">
            <input type="radio" wire:model.live="role" value="student" class="sr-only" />
            <div class="rounded-2xl border p-4 transition {{ $role === 'student' ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                <flux:heading>{{ __('Student') }}</flux:heading>
                <flux:text>{{ __('Focus on assignments, class deadlines, and study blocks.') }}</flux:text>
            </div>
        </label>

        <label class="block cursor-pointer" wire:click="$set('role', 'teacher')">
            <input type="radio" wire:model.live="role" value="teacher" class="sr-only" />
            <div class="rounded-2xl border p-4 transition {{ $role === 'teacher' ? 'border-cyan-500 bg-cyan-50 dark:border-cyan-400 dark:bg-cyan-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                <flux:heading>{{ __('Teacher') }}</flux:heading>
                <flux:text>{{ __('Prioritize lessons, grading, and preparation time.') }}</flux:text>
            </div>
        </label>

        <label class="block cursor-pointer" wire:click="$set('role', 'corporate_worker')">
            <input type="radio" wire:model.live="role" value="corporate_worker" class="sr-only" />
            <div class="rounded-2xl border p-4 transition {{ $role === 'corporate_worker' ? 'border-amber-500 bg-amber-50 dark:border-amber-400 dark:bg-amber-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                <flux:heading>{{ __('Corporate worker') }}</flux:heading>
                <flux:text>{{ __('Optimize around meetings, deep work, and reporting.') }}</flux:text>
            </div>
        </label>

        <flux:error name="role" />

        <flux:button type="submit" variant="primary" class="w-full">{{ __('Continue to scheduler') }}</flux:button>
    </form>
</section>
