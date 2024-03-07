<?php

namespace ALajusticia\Logins\Listeners;

use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Factories\LoginFactory;
use ALajusticia\Logins\Logins;
use ALajusticia\Logins\RequestContext;
use Illuminate\Events\Dispatcher;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class SanctumEventSubscriber
{
    /**
     * Handle personal access token creation event.
     *
     * @throws \Exception
     */
    public function handlePersonalAccessTokenCreation(PersonalAccessToken $personalAccessToken): void
    {
        // Get the authenticated model
        $model = $personalAccessToken->tokenable;

        if (Logins::tracked($model)) {

            // Get as much information as possible about the request
            $context = new RequestContext();

            // Build a new login
            $login = LoginFactory::buildFromSanctumToken($context, $personalAccessToken);

            // Set the expiration date
            $login->expiresAt($personalAccessToken->expires_at);

            // Attach the login to the model and save it
            $model->logins()->save($login);

            // Dispatch event
            event(new LoggedIn($model, $context));
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            'eloquent.created: ' . Sanctum::personalAccessTokenModel(),
            [SanctumEventSubscriber::class, 'handlePersonalAccessTokenCreation']
        );
    }
}
