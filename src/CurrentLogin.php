<?php

namespace ALajusticia\Logins;

use Illuminate\Support\Facades\Auth;
use ALajusticia\Logins\Models\Login;

class CurrentLogin
{
    public ?Login $currentLogin = null;

    public function __construct()
    {
        if (Auth::user() && Logins::tracked(Auth::user()) && ! $this->currentLogin) {
            if (Auth::user()->isAuthenticatedBySession()) {

                $this->currentLogin = Auth::user()->logins()
                    ->where('session_id', session()->getId())
                    ->first();

            } elseif (Auth::user()->isAuthenticatedBySanctumToken()) {

                $this->currentLogin = $this->logins()
                    ->where('personal_access_token_id', Auth::user()->currentAccessToken()->id)
                    ->first();

            }
        }
    }
}
