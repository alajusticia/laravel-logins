<?php

namespace ALajusticia\Logins\Models;

use ALajusticia\Expirable\Traits\Expirable;
use ALajusticia\Logins\Scopes\LoginsScope;
use ALajusticia\Logins\Traits\ManagesLogins;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'location' => 'array',
    ];

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return Config::get('logins.database_connection');
    }

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
     * Get the device/token name.
     */
    protected function device(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ! empty($value) ? $value : $this->personal_access_token?->name,
        );
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(Sanctum::$personalAccessTokenModel, 'personal_access_token_id');
    }

    /**
     * Get the "is_current" attribute.
     */
    public function getIsCurrentAttribute(): bool
    {
        if ($this->session_id && request()->hasSession()) {

            // Compare session ID
            return $this->session_id === request()->session()->getId();

        } elseif ($this->personal_access_token_id && request()->user()->isAuthenticatedBySanctumToken()) {

            // Compare Sanctum personal access token ID
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

            // Revoke Sanctum personal access token
            $this->revokeSanctumTokens($this->personal_access_token_id);

        }

        // Delete login
        return $this->delete();
    }
}
