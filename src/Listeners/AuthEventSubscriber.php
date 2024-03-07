<?php

namespace ALajusticia\Logins\Listeners;

use ALajusticia\Logins\Logins;
use ALajusticia\Logins\Models\Login;
use Illuminate\Auth\Recaller;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthEventSubscriber
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Listener for the `Login` event.
     *
     * Dispatched by Laravel on every successful initial or remembered login.
     */
    public function refreshSessionIdOnRememberedLogin(\Illuminate\Auth\Events\Login $event): void
    {
        if (Auth::viaRemember() && Logins::tracked($event->user)) {
                $this->updateSessionId();
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
            \Illuminate\Auth\Events\Login::class,
            [AuthEventSubscriber::class, 'refreshSessionIdOnRememberedLogin']
        );
    }
}
