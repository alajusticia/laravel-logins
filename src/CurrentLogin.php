<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Models\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CurrentLogin
{
    public ?Login $currentLogin = null;

    public function __construct()
    {
        $this->loadCurrentLogin();
    }

    public function loadCurrentLogin(?Authenticatable $user = null): void
    {
        $user = $user ?? Auth::user();

        if ($user && Logins::tracked($user) && ! $this->currentLogin) {
            if ($user->isAuthenticatedBySession()) {

                $this->currentLogin = $user->logins()
                    ->where('session_id', session()->getId())
                    ->first();

            } elseif (Config::get('logins.sanctum_token_tracking') && $user->isAuthenticatedBySanctumToken()) {

                $this->currentLogin = $user->logins()
                    ->where('personal_access_token_id', $user->currentAccessToken()->getKey())
                    ->first();

            }
        }
    }
}
