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

        $user = $this->user();

        if ($user && Logins::tracked($user)) {
            // Delete login
            $user->logins()->where('session_id', $this->session->getId())->delete();
        }

        parent::logout();
    }

    /**
     * Invalidate other sessions for the current user.
     *
     * The application must be using the AuthenticateSession middleware.
     *
     * @param  string  $password
     * @param  string  $attribute
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function logoutOtherDevices($password, $attribute = 'password')
    {
        $user = $this->user();

        if ($user && Logins::tracked($user)) {
            $user->logoutOthers();
            $this->fireOtherDeviceLogoutEvent($user);
        } else {
            return parent::logoutOtherDevices($password, $attribute);
        }
    }
}
