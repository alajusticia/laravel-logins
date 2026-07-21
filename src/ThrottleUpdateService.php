<?php

namespace ALajusticia\Logins;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ThrottleUpdateService
{
    private const CACHE_PREFIX = 'logins:activity:';

    private static function getInterval(): int
    {
        return (int) Config::get('logins.activity_update.interval', 0);
    }

    private static function getCacheStore(): ?string
    {
        return Config::get('logins.activity_update.cache_store');
    }

    private static function isThrottleEnabled(): bool
    {
        return self::getInterval() > 0;
    }

    public static function update(string|int $key, \Closure $callback): void
    {
        if (! self::isThrottleEnabled()) {
            $callback();
            return;
        }

        $key = self::CACHE_PREFIX . $key;
        $store = self::getCacheStore();

        try {
            $shouldUpdate = Cache::store($store)->add($key, true, self::getInterval());
        } catch (\Throwable $th) {
            // If the cache is unavailable, fall back to updating like the original behavior.
            $shouldUpdate = true;
        }

        if ($shouldUpdate) {
            $callback();
        }
    }
}
