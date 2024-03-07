<?php

namespace ALajusticia\Logins\Listeners;

use ALajusticia\Logins\Events\NewLogin;
use ALajusticia\Logins\Factories\LoginFactory;
use ALajusticia\Logins\Logins;
use ALajusticia\Logins\Models\Login;
use ALajusticia\Logins\RequestContext;
use Carbon\Carbon;
use Illuminate\Auth\Recaller;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuthEventSubscriber
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Listener for the `Authenticated` event.
     *
     * Dispatched by Laravel on all successful authentication.
     */
    public function handleSuccessfulAuthentication(\Illuminate\Auth\Events\Authenticated $event): void
    {
        if (Logins::tracked($event->user) && ! $event->user->current_login) {
            // If we don't already track the session ID
            // (it happens right after an initial login)
            $this->updateSessionId();
        }
    }

    /**
     * Listener for the `Login` event.
     *
     * Dispatched by Laravel on every successful initial or remembered login.
     */
    public function handleSuccessfulLogin(\Illuminate\Auth\Events\Login $event): void
    {
        if (Logins::tracked($event->user)) {

            if (Auth::viaRemember()) {
                // Logged in via remember token

                $this->updateSessionId();
            } else {
                // Initial login

                // Get as much information as possible about the request
                $context = new RequestContext;

                // Build a new login
                $login = LoginFactory::build($event, $context);

                // Set the expiration date based on whether it is a remembered login or not
                if ($event->remember) {
                    if ($rememberTokenLifetime = Config::get('logins.remember_token_lifetime')) {
                        $login->expiresAt(Carbon::now()->addDays($rememberTokenLifetime));
                    } else {
                        $login->expiresAt(null);
                    }
                } else {
                    $login->expiresAt(Carbon::now()->addMinutes(config('session.lifetime')));
                }

                // Attach the login to the user and save it
                $event->user->logins()->save($login);

                event(new NewLogin($event->user, $context));
            }
        }
    }

    /**
     * Listener for the `Logout` event.
     */
    public function handleSuccessfulLogout(\Illuminate\Auth\Events\Logout $event): void
    {
        if (Logins::tracked($event->user)) {
            // Delete login
            $event->user->logins()->where('session_id', session()->getId())
                ->delete();
        }
    }

    /**
     * Update session ID on Login model.
     */
    protected function updateSessionId(): void
    {
        if ($recaller = $this->request->cookies->get(Auth::guard()->getRecallerName())) {

            $recaller = new Recaller($recaller);

            Login::where('remember_token', $recaller->token())->update([
                'session_id' => $this->request->session()->getId()
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            \Illuminate\Auth\Events\Authenticated::class,
            [AuthEventSubscriber::class, 'handleSuccessfulAuthentication']
        );

        $events->listen(
            \Illuminate\Auth\Events\Login::class,
            [AuthEventSubscriber::class, 'handleSuccessfulLogin']
        );

        $events->listen(
            \Illuminate\Auth\Events\Logout::class,
            [AuthEventSubscriber::class, 'handleSuccessfulLogout']
        );
    }
}
