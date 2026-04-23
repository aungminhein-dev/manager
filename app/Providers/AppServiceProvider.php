<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Avoid creating cache directories during artisan commands that might run
        // as root inside Docker; web requests (php-fpm as www-data) will ensure
        // these directories with safe ownership.
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->ensureLivewireCacheDirectories();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Livewire page components compile into storage/framework/cache/livewire-components.
     * On some bind-mounted environments these folders disappear or become non-writable,
     * which makes tempnam() fall back to /tmp and raises an exception in Laravel.
     */
    protected function ensureLivewireCacheDirectories(): void
    {
        $directories = [
            storage_path('framework/views/livewire'),
            storage_path('framework/views/livewire/classes'),
            storage_path('framework/views/livewire/views'),
            storage_path('framework/views/livewire/scripts'),
            storage_path('framework/views/livewire/styles'),
            storage_path('framework/views/livewire/placeholders'),
        ];

        foreach ($directories as $directory) {
                if (! is_dir($directory)) {
                    // Race-safe mkdir for concurrent requests during app boot.
                    if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                        continue;
                    }
            }

            if (! is_writable($directory)) {
                @chmod($directory, 0777);
            }
        }
    }
}
