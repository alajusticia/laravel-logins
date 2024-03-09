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
        // Session is destroyed on login to prevent session hijacking, and therefore its ID changes.
        // This check is necessary because, depending on the session driver we used, the new session may not yet
        // have been persisted and the new session ID may not be available when the Login event is dispatched.

        if (Logins::tracked($event->user) && ! $event->user->current_login) {

            // We don't already track the session ID

            if ($loginId = session('login_id')) {
                // Just logged in

                Login::where('id', $loginId)->update(['session_id' => session()->getId()]);

            } elseif ($recallerCookie = request()->cookies->get(Auth::guard()->getRecallerName())) {
                // Authenticated via remember token

                $recaller = new Recaller($recallerCookie);

                Login::where('remember_token', $recaller->token())->update([
                    'session_id' => request()->session()->getId()
                ]);
            }
        }
    }

    /**
     * Listener for the `Login` event.
     *
     * Dispatched by Laravel on every successful initial or remembered login.
     */
    public function handleSuccessfulLogin(\Illuminate\Auth\Events\Login $event): void
    {
        if (! Auth::viaRemember() && Logins::tracked($event->user)) {
            Logins::trackLoginFromSession(session()->getId(), $event->user, $event->remember);
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
