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
            // Support Cloudflare proxy by checking if HTTP_CF_CONNECTING_IP header exists
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
        $user->logins()->save($login);

        session(['login_id' => $login->id]);

        // Dispatch event
        event(new LoggedIn($user, $context));
    }

    public static function checkSessionId($user)
    {
        // Session is ID changes on login to prevent session hijacking.
        // This check is necessary because the new session ID may not yet be available when the Login event is dispatched.

        if (Logins::tracked($user) && ! $user->current_login) {

            // We don't already track the session ID

            $updated = 0;

            if ($loginId = session('login_id')) {
                // Just logged in

                $updated = Login::where('id', $loginId)->update(['session_id' => session()->getId()]);

            } elseif ($recallerCookie = request()->cookies->get(Auth::guard()->getRecallerName())) {
                // Authenticated via remember token

                $recaller = new Recaller($recallerCookie);

                $updated = Login::where('remember_token', $recaller->token())->update([
                    'session_id' => request()->session()->getId()
                ]);
            }

            if ($updated > 0) {
                app(CurrentLogin::class)->loadCurrentLogin();
            }
        }
    }
}
