<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Models\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CurrentLogin
{
    public ?Login $currentLogin = null;

    public function __construct()
    {
        $this->loadCurrentLogin();
    }

    public function loadCurrentLogin(): void
    {
        if (Auth::user() && Logins::tracked(Auth::user()) && ! $this->currentLogin) {
            if (Auth::user()->isAuthenticatedBySession()) {

                $this->currentLogin = Auth::user()->logins()
                    ->where('session_id', session()->getId())
                    ->first();

            } elseif (Config::get('logins.sanctum_token_tracking') && Auth::user()->isAuthenticatedBySanctumToken()) {

                $this->currentLogin = $this->logins()
                    ->where('personal_access_token_id', Auth::user()->currentAccessToken()->getKey())
                    ->first();

            }
        }
    }
}
