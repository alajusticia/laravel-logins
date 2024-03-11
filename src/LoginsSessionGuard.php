<?php

namespace ALajusticia\Logins;

use Illuminate\Auth\SessionGuard;

class LoginsSessionGuard extends SessionGuard
{
    protected function deleteCurrentLogin() {
        $user = $this->user();

        if ($user && Logins::tracked($user)) {
            // Delete login
            $user->logins()->where('session_id', $this->session->getId())->delete();
        }
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        $this->deleteCurrentLogin();

        parent::logout();
    }

    /**
     * Log the user out of the application on their current device only.
     *
     * This method does not cycle the "remember" token.
     *
     * @return void
     */
    public function logoutCurrentDevice()
    {
        $this->deleteCurrentLogin();

        parent::logoutCurrentDevice();
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
