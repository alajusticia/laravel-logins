<?php

namespace ALajusticia\Logins\Factories;

use ALajusticia\Logins\Models\Login;
use ALajusticia\Logins\RequestContext;
use Illuminate\Auth\Events\Login as LoginEvent;
use Laravel\Sanctum\PersonalAccessToken;

class LoginFactory
{
    /**
     * Build a new Login model.
     */
    public static function build(
        LoginEvent|PersonalAccessToken $trigger,
        RequestContext $context
    ): Login
    {
        $login = new Login();

        // Common attributes ------------------------------------------------------------------

        $login->fill([
            'user_agent' => $context->userAgent(),
            'ip_address' => $context->ipAddress(),
            'device_type' => $context->parser()->getDeviceType(),
            'device' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
            'location' => $context->location() ?? null,
        ]);

        // Attributes depending on the authentication method ----------------------------------

        if ($trigger instanceof PersonalAccessToken) {
            // Laravel Sanctum

            $login->personal_access_token_id = $trigger->id;

        } else {
            // Session

            $login->fill([
                'session_id' => session()->getId(),
                'remember_token' => $trigger->remember ? $trigger->user->getRememberToken() : null,
            ]);

        }

        // ------------------------------------------------------------------------------------

        return $login;
    }
}
