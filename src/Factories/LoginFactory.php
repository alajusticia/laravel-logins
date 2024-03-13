<?php

namespace ALajusticia\Logins\Factories;

use ALajusticia\Logins\Models\Login;
use ALajusticia\Logins\RequestContext;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Contracts\HasAbilities;

class LoginFactory
{
    public static function buildFromLogin(
        RequestContext $context,
        string $sessionId,
        string $guard,
        Authenticatable $user,
        bool $remember,
    ): Login
    {
        $login = self::getNewLoginWithContext($context);

        $login->fill([
            'session_id' => $sessionId,
            'remember_token' => $remember ? $user->getRememberToken() : null,
        ]);

        // Set the expiration date based on whether it's a remembered login or not
        $login->expiresAt(
            $remember
                ? Carbon::now()->addMinutes((int) Config::get('auth.guards.' . $guard . '.remember', 576000)) // Same default value as in the SessionGuard
                : Carbon::now()->addMinutes((int) Config::get('session.lifetime', 120))
        );

        return $login;
    }

    public static function buildFromSanctumToken(
        RequestContext $context,
        HasAbilities $token
    ): Login
    {
        $login = self::getNewLoginWithContext($context);

        $login->personal_access_token_id = $token->getKey();

        if ($tokenExpiration = Config::get('sanctum.expiration')) {
            $login->expiresAt(Carbon::now()->addMinutes((int) $tokenExpiration));
        }

        return $login;
    }

    protected static function getNewLoginWithContext(RequestContext $context): Login
    {
        return new Login([
            'user_agent' => $context->userAgent(),
            'ip_address' => $context->ipAddress(),
            'device_type' => $context->parser()->getDeviceType(),
            'device' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
            'location' => $context->location() ?? null,
        ]);
    }
}
