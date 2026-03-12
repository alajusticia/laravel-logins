<?php

namespace ALajusticia\Logins;

class Helpers
{
    /**
     * Check if Inertia Laravel is installed.
     */
    public static function inertiaLaravelIsInstalled(): bool
    {
        return is_dir(base_path('vendor/inertiajs/inertia-laravel'));
    }

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

    /**
     * Check if Laravel Livewire Starter Kit is installed.
     */
    public static function livewireStarterKitIsInstalled(): bool
    {
        return self::livewireStarterKitSingleFileVariantIsInstalled()
            || self::livewireStarterKitClassBasedVariantIsInstalled();
    }

    /**
     * Check if Laravel Livewire Starter Kit (single-file component variant) is installed.
     */
    public static function livewireStarterKitSingleFileVariantIsInstalled(): bool
    {
        return self::livewireIsInstalled()
            && is_dir(base_path('vendor/livewire/flux'))
            && is_file(base_path('routes/settings.php'))
            && is_file(resource_path('views/pages/settings/layout.blade.php'));
    }

    /**
     * Check if Laravel Livewire Starter Kit (class-based variant) is installed.
     */
    public static function livewireStarterKitClassBasedVariantIsInstalled(): bool
    {
        return self::livewireIsInstalled()
            && is_dir(base_path('vendor/livewire/flux'))
            && is_file(base_path('routes/settings.php'))
            && is_file(app_path('Livewire/Settings/Profile.php'))
            && is_file(resource_path('views/livewire/settings/profile.blade.php'))
            && is_file(resource_path('views/components/settings/layout.blade.php'));
    }

    /**
     * Check if Laravel Vue Starter Kit is installed.
     */
    public static function laravelVueStarterKitIsInstalled(): bool
    {
        return self::inertiaLaravelIsInstalled()
            && is_file(base_path('routes/settings.php'))
            && is_file(app_path('Http/Controllers/Settings/ProfileController.php'))
            && is_file(resource_path('js/layouts/settings/Layout.vue'))
            && is_file(resource_path('js/pages/settings/Profile.vue'));
    }
}
