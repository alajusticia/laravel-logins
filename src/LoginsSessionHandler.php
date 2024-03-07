<?php

namespace ALajusticia\Logins;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\DatabaseSessionHandler;

class LoginsSessionHandler extends DatabaseSessionHandler
{
    /**
     * Add the user information to the session payload.
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        if ($this->container->bound(Guard::class)) {
            $payload['authenticatable_type'] = $this->user;
            $payload['authenticatable_id'] = $this->userId();
        }

        return $this;
    }
}
