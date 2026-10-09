<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Factories\LoginFactory;
use ALajusticia\Logins\Models\Login;
use Illuminate\Auth\Recaller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Throwable;

class Logins
{
    /**
     * Determine if the package-managed routes should be registered
     */
    protected static bool $registerRoutes = false;

    /**
     * The callback that is responsible for retrieving the client's IP address, if applicable.
     *
     * @var callable|null
     */
    protected static $getIpAddressUsingCallback = null;

    /**
     * The callback describing the device of a login, when the User-Agent does not.
     *
     * @var callable|null
     */
    protected static $describeDeviceUsingCallback = null;

    /**
     * Determine if new login notifications are sent.
     */
    protected static bool $notificationsEnabled = true;

    /**
     * Register a callback describing the device of a login, for clients whose User-Agent does not
     * describe the device (a mobile app using a generic HTTP client, for example).
     *
     * The callback receives the request context and returns the values to use instead of the parsed
     * ones, among `device_type`, `device`, `platform` and `browser`, or null to keep the parsed values.
     *
     * @param callable(RequestContext): ?array{device_type?: ?string, device?: ?string, platform?: ?string, browser?: ?string}|null $callback
     */
    public static function describeDeviceUsing(?callable $callback): void
    {
        static::$describeDeviceUsingCallback = $callback;
    }

    /**
     * Get the device description of the current login, from the registered callback.
     *
     * @return array<string, ?string>
     */
    public static function describeDevice(RequestContext $context): array
    {
        if (static::$describeDeviceUsingCallback === null) {
            return [];
        }

        $description = call_user_func(static::$describeDeviceUsingCallback, $context);

        return array_intersect_key((array) $description, array_flip(['device_type', 'device', 'platform', 'browser']));
    }

    /**
     * Run the callback without sending new login notifications, for the logins it creates.
     *
     * Unlike the `notifyLogins` property of the model, it also applies to Sanctum personal access tokens,
     * whose login is attached to the token's model as retrieved from the database.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     * @return TReturn
     */
    public static function withoutNotifications(callable $callback): mixed
    {
        $enabled = static::$notificationsEnabled;
        static::$notificationsEnabled = false;

        try {
            return $callback();
        } finally {
            static::$notificationsEnabled = $enabled;
        }
    }

    /**
     * Determine if new login notifications are sent.
     */
    public static function notificationsEnabled(): bool
    {
        return static::$notificationsEnabled;
    }

    /**
     * Register a callback that is responsible for retrieving the client's IP address.
     *
     * @param callable(): ?string $callback
     */
    public static function getIpAddressUsing(callable $callback): void
    {
        static::$getIpAddressUsingCallback = $callback;
    }

    /**
     * Opt in to registering the package-managed routes.
     */
    public static function registerRoutes(bool $register = true): void
    {
        static::$registerRoutes = $register;
    }

    /**
     * Determine if the package-managed routes should be registered.
     */
    public static function shouldRegisterRoutes(): bool
    {
        return static::$registerRoutes;
    }

    /**
     * Get the client's IP address.
     */
    public static function ipAddress(): ?string
    {
        if (static::$getIpAddressUsingCallback !== null) {
            return call_user_func(static::$getIpAddressUsingCallback);
        }

        return request()->ip();
    }

    /**
     * Tracking enabled for this model?
     */
    public static function tracked(Authenticatable $model): bool
    {
        return in_array('ALajusticia\Logins\Traits\HasLogins', class_uses_recursive($model))
               && $model->trackLogins;
    }

    /**
     * Check if the IP geolocation is enabled.
     */
    public static function ipGeolocationEnabled(): bool
    {
        $environments = Config::get('logins.ip_geolocation.environments');

        return ! empty($environments) && App::environment($environments);
    }

    public static function trackLoginFromSession(
        string $sessionId,
        string $guard,
        Authenticatable $user,
        bool $remember = false
    ): void
    {
        // Get as much information as possible about the request
        $context = new RequestContext;

        // Build a new login
        $login = LoginFactory::buildFromLogin(
            $context, $sessionId, $guard, $user, $remember
        );

        // Attach the login to the user and save it
        if (! self::storeLogin($user, $login)) {
            return;
        }

        session(['login_id' => $login->id]);

        // Dispatch event
        event(new LoggedIn($user, $context));
    }

    public static function storeLogin(Authenticatable $user, Login $login): bool
    {
        try {
            $user->logins()->save($login);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public static function checkSessionId($user)
    {
        // Session ID changes on login to prevent session hijacking.
        // This check is necessary because the new session ID may not yet be available when the Login event is dispatched.

        if (Logins::tracked($user)) {

            $updated = 0;

            if (! $user->current_login) {

                // We don't already track the session ID

                if ($loginId = session('login_id')) {
                    // Just logged in

                    $updated = Login::where('id', $loginId)->update([
                        'session_id' => session()->getId(),
                        'last_activity_at' => now(),
                    ]);

                } elseif ($recallerCookie = request()->cookies->get(Auth::guard()->getRecallerName())) {
                    // Authenticated via remember token

                    $recaller = new Recaller($recallerCookie);

                    $updated = Login::where('remember_token', $recaller->token())->update([
                        'session_id' => request()->session()->getId(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            app(CurrentLogin::class)->loadCurrentLogin($user);

            if ($updated === 0) {
                self::updateLastActivity();
            }
        }
    }

    public static function updateLastActivity()
    {
        if ($currentLogin = app(CurrentLogin::class)->currentLogin) {
            ThrottleUpdateService::update($currentLogin->getKey(), function () use ($currentLogin) {
                $currentLogin->update([
                    'ip_address' => self::ipAddress(),
                    'last_activity_at' => now(),
                ]);
            });
        }
    }
}
