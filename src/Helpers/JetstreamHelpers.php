<?php

namespace ALajusticia\Logins\Helpers;

class JetstreamHelpers
{
    /**
     * Check if Laravel Sanctum is installed.
     */
    public static function jetstreamIsInstalled(): bool
    {
        return is_dir(base_path('vendor/laravel/jetstream'));
    }
}
