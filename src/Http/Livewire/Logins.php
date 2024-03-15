<?php

namespace ALajusticia\Logins\Http\Livewire;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Logins extends Component
{
    /**
     * Indicates if logout is being confirmed.
     *
     * @var bool
     */
    public $confirmingLogout = false;
    public $confirmingLogoutSingle = false;

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

    /**
     * Confirm that the user would like to log out from other browser sessions.
     *
     * @return void
     */
    public function confirmLogout()
    {
        $this->password = '';

        $this->dispatch('confirming-logout-other-browser-sessions');

        $this->confirmingLogout = true;
    }

    /**
     * Confirm that the user would like to log out a specific session.
     *
     * @return void
     */
    public function confirmLogoutSingle()
    {
        $this->password = '';

        $this->dispatch('confirming-logout-single-session');

        $this->confirmingLogoutSingle = true;
    }

    /**
     * Log out from other browser sessions.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function logoutOtherDevices(StatefulGuard $guard)
    {
        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        $guard->logoutOtherDevices($this->password);

        $this->confirmingLogout = false;

        $this->dispatch('loggedOut');
    }

    /**
     * Log out from other browser sessions.
     *
     * @param  int  $loginId
     * @return void
     */
    public function logoutSingleSession(int $loginId): void
    {
        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        Auth::user()->logout($loginId);

        $this->confirmingLogoutSingle = false;

        $this->dispatch('loggedOut');
    }

    /**
     * Get the logins.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLoginsProperty()
    {
        return request()->user()->logins()->orderBy('last_activity_at', 'desc')->get();
    }

    public function render()
    {
        return view('logins');
    }
}
