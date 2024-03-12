<?php

namespace ALajusticia\Logins\Listeners;

use ALajusticia\Logins\Logins;
use ALajusticia\Logins\Models\Login;
use Illuminate\Auth\Recaller;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;

class SessionEventSubscriber
{
    /**
     * Listener for the `Authenticated` event.
     *
     * Dispatched by Laravel on all successful authentication.
     */
    public function handleSuccessfulAuthentication(\Illuminate\Auth\Events\Authenticated $event): void
    {
        Logins::checkSessionId($event->user);
    }

    /**
     * Listener for the `Login` event.
     *
     * Dispatched by Laravel on every successful initial or remembered login.
     */
    public function handleSuccessfulLogin(\Illuminate\Auth\Events\Login $event): void
    {
        if (! Auth::viaRemember() && Logins::tracked($event->user)) {
            Logins::trackLoginFromSession(session()->getId(), $event->guard, $event->user, $event->remember);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            \Illuminate\Auth\Events\Authenticated::class,
            [SessionEventSubscriber::class, 'handleSuccessfulAuthentication']
        );

        $events->listen(
            \Illuminate\Auth\Events\Login::class,
            [SessionEventSubscriber::class, 'handleSuccessfulLogin']
        );
    }
}
