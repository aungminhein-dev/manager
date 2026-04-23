<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        <div class="relative isolate min-h-screen overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_15%,rgba(255,255,255,0.13),transparent_38%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_82%_78%,rgba(255,255,255,0.09),transparent_44%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_108%,rgba(255,255,255,0.07),transparent_42%)]"></div>
            </div>

            <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6 md:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium backdrop-blur" wire:navigate>
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-black">
                        <x-app-logo-icon class="size-4 fill-current" />
                    </span>
                    <span>{{ config('app.name', 'Laravel') }}</span>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 sm:gap-3 text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 font-medium text-white hover:bg-white/20" wire:navigate>
                                {{ __('Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-[#6c757d] hover:text-white" wire:navigate>
                                {{ __('Log in') }}
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-full border border-white/20 bg-white px-4 py-2 font-medium text-black hover:bg-white/90" wire:navigate>
                                    {{ __('Register') }}
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="relative mx-auto flex w-full max-w-6xl items-center px-4 pb-12 pt-10 sm:px-6 sm:pt-16 md:px-8 md:pt-20">
                <section class="w-full rounded-3xl border border-white/15 bg-white/5 p-6 backdrop-blur-xl sm:p-8 md:p-10">
                    <p class="text-xs font-medium tracking-[0.18em] text-[#6c757d]">{{ __('PRODUCTIVITY PLATFORM') }}</p>
                    <h1 class="mt-4 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl md:text-5xl">
                        {{ __('Minimal by design, focused on what matters.') }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-[#6c757d] sm:text-base">
                        {{ __('Manage schedules, tasks, and daily planning with a clean workspace built for speed and clarity.') }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white px-5 py-2.5 text-sm font-semibold text-black hover:bg-white/90" wire:navigate>
                                {{ __('Open dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white px-5 py-2.5 text-sm font-semibold text-black hover:bg-white/90" wire:navigate>
                                {{ __('Create account') }}
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10" wire:navigate>
                                {{ __('Sign in') }}
                            </a>
                        @endauth
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
