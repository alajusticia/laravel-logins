<?php

namespace ALajusticia\Logins\Events;

use ALajusticia\Logins\RequestContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class NewLogin
{
    use SerializesModels;

    public function __construct(

        /**
         * The authenticated model.
         */
        public Authenticatable $authenticatable,

        /**
         * Information about the request (user agent, ip address...).
         */
        public RequestContext $context
    ) {}
}
