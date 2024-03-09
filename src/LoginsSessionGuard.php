<?php

namespace ALajusticia\Logins;

use Illuminate\Auth\SessionGuard;

class LoginsSessionGuard extends SessionGuard
{
    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        // To be able to link the old session to its corresponding login,
        // we needed to extend the session guard and override the logout() method.
        // This way, we make sure we get the correct session ID before it is regenerated.

        if (Logins::tracked($this->user())) {
            // Delete login
            $this->user()->logins()->where('session_id', $this->session->getId())->delete();
        }

        parent::logout();
    }
}
