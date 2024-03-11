<?php

namespace ALajusticia\Logins\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

trait ManagesLogins
{
    /**
     * Destroy the given session id.
     */
    protected function destroySession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            Auth::logout();
        } else {
            session()->getHandler()->destroy($sessionId);
        }
    }

    /**
     * Revoke the given Sanctum personal access token ids.
     */
    protected function revokeSanctumTokens(Collection|array|int $personalAccessTokenIds): void
    {
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
