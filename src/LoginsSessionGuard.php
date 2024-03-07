<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Factories\LoginFactory;
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

        if (Logins::tracked($user)) {

            // Get as much information as possible about the request
            $context = new RequestContext;

            // Build a new login
            $login = LoginFactory::buildFromLogin(
                $context, $this->session->getId(), $user, $remember
            );

            // Attach the login to the user and save it
            $user->logins()->save($login);

            // Dispatch event
            event(new LoggedIn($user, $context));
        }
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        if (Logins::tracked($this->user())) {
            // Delete login
            $this->user()->logins()->where('session_id', $this->session->getId())->delete();
        }

        parent::logout();
    }
}
