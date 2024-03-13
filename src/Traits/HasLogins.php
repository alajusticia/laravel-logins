<?php

namespace ALajusticia\Logins\Traits;

use ALajusticia\Logins\CurrentLogin;
use ALajusticia\Logins\Models\Login;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\Sanctum;

trait HasLogins
{
    public ?string $loginRememberToken = null;

    /**
     * Get all the user's logins.
     */
    public function logins(): MorphMany
    {
        return $this->morphMany(Login::class, 'authenticatable');
    }

    /**
     * Get the current user's login.
     */
    public function getCurrentLoginAttribute(): ?Login
    {
        return app(CurrentLogin::class)->currentLogin;
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @throws \Exception
     */
    public function logout(?int $loginId = null): bool
    {
        $login = $loginId ? $this->logins()->find($loginId) : $this->current_login;

        return $login && ! empty($login->revoke());
    }

    /**
     * Destroy all sessions / Revoke all sanctum tokens, except the current one.
     */
    public function logoutOthers(): mixed
    {
        if ($this->isAuthenticatedBySession()) {

            return $this->logins()
                        ->where(function (Builder $query) {
                            return $query
                                ->where('session_id', '!=', session()->getId())
                                ->orWhereNull('session_id');
                        })
                        ->revoke();

        } elseif ($this->isAuthenticatedBySanctumToken()) {

            return $this->logins()
                        ->where(function (Builder $query) {
                            return $query
                                ->where('personal_access_token_id', '!=', $this->currentAccessToken()->id)
                                ->orWhereNull('personal_access_token_id');
                        })
                        ->revoke();
        }

        return false;
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     */
    public function logoutAll(): mixed
    {
        return $this->logins()->revoke();
    }

    /**
     * Determine if current user is authenticated via a session.
     */
    public function isAuthenticatedBySession(): bool
    {
        return request()->hasSession() && ! is_null(request()->user());
    }

    /**
     * Check for authentication via Sanctum.
     */
    public function isAuthenticatedBySanctumToken(): bool
    {
        return in_array(HasApiTokens::class, class_uses_recursive($this))
            && $this->currentAccessToken() instanceof Sanctum::$personalAccessTokenModel;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->loginRememberToken;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->loginRememberToken = $value;
    }
}
