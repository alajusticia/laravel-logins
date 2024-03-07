<?php

namespace ALajusticia\Logins;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class LoginsSessionGuard extends SessionGuard
{
    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = false): void
    {


        parent::login($user, $remember);
    }
}
