<?php

namespace ALajusticia\Logins\Listeners;

use ALajusticia\Logins\Events\LoggedIn;
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
        // Session is destroyed on login to prevent session hijacking, and therefore its ID changes.
        // This check is necessary because, depending on the session driver used, the new session may not yet have been
        // persisted and the new session ID may not be available when the Login event is dispatched.
        // At first, I was not getting the right session ID, and it took me time to understand the whole process.
        // In my attempt to get it work, I built my own authentication guard and my own session driver, but then I realized
        // that it was over-complicating all the package, adding two more pieces to configure and that's not what I
        // wanted. I like simple and maintenable solutions. So, I found another way, much simpler, and I came back to my
        // first idea, using only an event listener.

        if (Logins::tracked($event->user) && ! $event->user->current_login && $loginId = session('login_id')) {
            // We don't already track the session ID (just logged in)
            Login::where('id', $loginId)
                ->update(['session_id' => session()->getId()]);
        }
    }

    /**
     * Listener for the `Login` event.
     *
     * Dispatched by Laravel on every successful initial or remembered login.
     */
    public function handleSuccessfulLogin(\Illuminate\Auth\Events\Login $event): void
    {
        // wip
        if (Auth::viaRemember() && Logins::tracked($event->user)) {
                $this->updateSessionIdAfterRecall();
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
    protected function updateSessionIdAfterRecall(): void
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

//        $events->listen(
//            \Illuminate\Auth\Events\Logout::class,
//            [AuthEventSubscriber::class, 'handleSuccessfulLogout']
//        );
    }
}
