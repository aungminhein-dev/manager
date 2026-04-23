<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-5">
        <x-auth-header :title="__('Create your account')" :description="__('A clean workspace starts with a quick sign up.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:heading>{{ __('Please fix the form errors below.') }}</flux:heading>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />
            <flux:error name="name" />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />
            <flux:error name="email" />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />
            <flux:error name="password" />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />
            <flux:error name="password_confirmation" />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-[#6c757d]">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link class="!text-white hover:!text-[#6c757d]" :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
