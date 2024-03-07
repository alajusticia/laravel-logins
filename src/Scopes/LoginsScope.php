<?php

namespace ALajusticia\Logins\Scopes;

use ALajusticia\Logins\Traits\ManagesLogins;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LoginsScope implements Scope
{
    use ManagesLogins;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('revoke', function (Builder $builder) {
            $logins = $builder->get();

            if ($logins->isNotEmpty()) {

                // Destroy sessions
                foreach ($logins->pluck('session_id')->filter() as $sessionId) {
                    $this->destroySession($sessionId);
                }

                // Revoke Sanctum tokens
                $this->revokeSanctumTokens($logins->pluck('personal_access_token_id')->filter());

                // Delete logins
                return $builder->delete();
            }

            return false;
        });
    }
}
