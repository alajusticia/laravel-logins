<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Commands\Install;
use ALajusticia\Logins\Commands\Publish;
use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Listeners\SanctumEventSubscriber;
use ALajusticia\Logins\Listeners\SessionEventSubscriber;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class LoginsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__.'/../config/logins.php', 'logins'
        );

        // Register commands
        $this->commands([
            Install::class,
            Publish::class,
        ]);

        $this->app->singleton(CurrentLogin::class, function (Application $app) {
            return new CurrentLogin();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Allow publishing config
        $this->publishes([
            __DIR__.'/../config/logins.php' => config_path('logins.php'),
        ], 'logins-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Configure our authentication guard
        $this->configureGuard();

        // Register custom Eloquent user provider
        Auth::provider('logins', function (Application $app, array $config) {
            return new LoginsUserProvider($app['hash'], $config['model']);
        });

        // Register event listeners
        Event::subscribe(SessionEventSubscriber::class);
        if (Config::get('logins.sanctum_token_tracking')) {
            Event::subscribe(SanctumEventSubscriber::class);
        }
        if ($notificationClass = Config::get('logins.new_login_notification')) {
            Event::listen(function (LoggedIn $event) use ($notificationClass) {
                if ($event->authenticatable->notifyLogins) {
                    $event->authenticatable->notify(new $notificationClass($event->context->toArray()));
                }
            });
        }

        // Register Blade directives
        Blade::if('logins', function () {
            $user = Request::user();

            return is_object($user) && method_exists($user, 'logins');
        });

        $this->registerPackageRoutes();

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'logins');

        // Allow publishing translations
        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/alajusticia/logins'),
        ], 'logins-lang');
    }

    /**
     * Configure our authentication guard.
     */
    protected function configureGuard(): void
    {
        Auth::extend('logins', function (Application $app, string $name, array $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);

            $guard = new LoginsSessionGuard(
                $name,
                $provider,
                $app['session.store'],
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }

            return $guard;
        });
    }

    /**
     * Register the package-managed routes when explicitly enabled by the host app.
     */
    protected function registerPackageRoutes(): void
    {
        $this->app->booted(function () {
            if (! Logins::shouldRegisterPackageRoutes()) {
                return;
            }

            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
