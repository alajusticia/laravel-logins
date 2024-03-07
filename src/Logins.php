<?php

namespace ALajusticia\Logins;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class Logins
{
    /**
     * The callback that is responsible for retrieving the client's IP address, if applicable.
     *
     * @var callable|null
     */
    protected static $getIpAddressUsingCallback = null;

    /**
     * Register a callback that is responsible for retrieving the client's IP address.
     */
    public static function getIpAddressUsing(callable $callback): void
    {
        static::$getIpAddressUsingCallback = $callback;
    }

    /**
     * Get the client's IP address.
     */
    public static function ipAddress(): string
    {
        if (static::$getIpAddressUsingCallback) {
            return call_user_func(static::$getIpAddressUsingCallback);
        } else {
            // Supports Cloudflare proxy by checking if HTTP_CF_CONNECTING_IP header exists
            // Fallback to built-in Laravel ip() method on Request
            return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? Request::ip();
        }
    }

    /**
     * Tracking enabled for this model?
     */
    public static function tracked(Authenticatable $model): bool
    {
        return in_array('ALajusticia\Logins\Traits\HasLogins', class_uses_recursive($model));
    }

    /**
     * Check if the IP geolocation is enabled.
     */
    public static function ipGeolocationEnabled(): bool
    {
        $environments = config('logins.ip_geolocation.environments');

        return ! empty($environments) && App::environment($environments);
    }
}
