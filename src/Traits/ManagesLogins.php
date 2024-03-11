<?php

namespace ALajusticia\Logins\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

trait ManagesLogins
{
    /**
     * Destroy the given session id.
     */
    protected function destroySession(string $sessionId): void
    {
        session()->getHandler()->destroy($sessionId);

        if (! app()->runningInConsole() && request()->hasSession() && $sessionId === session()->getId()) {
            // Destroying current session
            session()->invalidate();
            session()->regenerateToken();
        }
    }

    /**
     * Revoke the given Sanctum personal access token ids.
     */
    protected function revokeSanctumTokens(Collection|array|int $personalAccessTokenIds): void
    {
        if (Config::get('logins.sanctum_token_tracking')) {
            // Support for collections
            if ($personalAccessTokenIds instanceof Collection) {
                $personalAccessTokenIds = $personalAccessTokenIds->all();
            }

            // Convert parameters into an array if needed
            $personalAccessTokenIds = is_array($personalAccessTokenIds) ? $personalAccessTokenIds : func_get_args();

            if (! empty($personalAccessTokenIds)) {
                PersonalAccessToken::whereIn('id', $personalAccessTokenIds)
                    ->delete();
            }
        }
    }
}
