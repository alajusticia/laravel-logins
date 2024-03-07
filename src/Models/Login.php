<?php

namespace ALajusticia\Logins\Models;

use ALajusticia\Expirable\Traits\Expirable;
use ALajusticia\Logins\Scopes\LoginsScope;
use ALajusticia\Logins\Traits\ManagesLogins;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Login extends Model
{
    use Expirable;
    use ManagesLogins;
    use SoftDeletes;

    const EXPIRES_AT = 'expires_at';

    protected $table = 'logins';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'authenticatable_type',
        'authenticatable_id',
        'session_id',
        'remember_token',
        'personal_access_token_id',
        'expires_at',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_current',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new LoginsScope);
    }

    /**
     * Relation between Login and an authenticatable model.
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Add the "location" attribute to get the IP address geolocation.
     */
    public function getLocationAttribute(): ?string
    {
        $location = [
            $this->city,
            $this->region,
            $this->country,
        ];

        return array_filter($location) ? implode(', ', $location) : null;
    }

    /**
     * Dynamicly add the "is_current" attribute.
     */
    public function getIsCurrentAttribute(): bool
    {
        if ($this->session_id && request()->hasSession()) {

            // Session

            return $this->session_id === request()->session()->getId();

        } elseif ($this->personal_access_token_id && request()->user()->isAuthenticatedBySanctumToken()) {

            // Sanctum

            return $this->personal_access_token_id === request()->user()->currentAccessToken()->id;
        }

        return false;
    }

    /**
     * Revoke the login.
     *
     * @throws \Exception
     */
    public function revoke(): ?bool
    {
        if ($this->session_id) {

            // Destroy session
            $this->destroySession($this->session_id);

        } elseif ($this->personal_access_token_id) {

            // Revoke Sanctum token
            $this->revokeSanctumTokens($this->personal_access_token_id);

        }

        // Delete login
        return $this->delete();
    }
}
