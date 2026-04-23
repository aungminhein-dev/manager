<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-black text-white">
        <div class="relative flex min-h-svh items-center justify-center overflow-hidden p-4 sm:p-6 md:p-10">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(255,255,255,0.14),transparent_45%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_82%,rgba(255,255,255,0.1),transparent_48%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_105%,rgba(255,255,255,0.08),transparent_45%)]"></div>
            </div>

            <div class="relative w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-5 inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-medium text-[#6c757d] shadow-lg shadow-black/40 backdrop-blur" wire:navigate>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-black">
                        <x-app-logo-icon class="size-4 fill-current" />
                    </span>
                    <span class="text-white">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="rounded-3xl border border-white/15 bg-white/5 p-6 shadow-2xl shadow-black/60 backdrop-blur-xl sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-4 text-center text-xs text-[#6c757d]">
                    {{ __('Secure access with modern, minimal UI.') }}
                </p>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
