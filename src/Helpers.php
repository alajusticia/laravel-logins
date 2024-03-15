<?php

namespace ALajusticia\Logins;

class Helpers
{
    /**
     * Check if Laravel Sanctum is installed.
     */
    public static function sanctumIsInstalled(): bool
    {
        return is_dir(base_path('vendor/laravel/sanctum'));
    }

    /**
     * Check if Laravel Jetstream is installed.
     */
    public static function jetstreamIsInstalled(): bool
    {
        return is_dir(base_path('vendor/laravel/jetstream'));
    }

    /**
     * Check if Livewire is installed.
     */
    public static function livewireIsInstalled(): bool
    {
        return is_dir(base_path('vendor/livewire'));
    }
}
