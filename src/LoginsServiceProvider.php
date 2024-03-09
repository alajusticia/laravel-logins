<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Commands\Install;
use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Notifications\NewLogin;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
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
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Configure our authentication guard
        $this->configureGuard();

        // Register custom Eloquent user provider
        Auth::provider('logins', function (Application $app, array $config) {
            return new LoginsUserProvider($app['hash'], $config['model']);
        });

        // Register custom session driver
//        Session::extend('logins', function (Application $app) {
//            $connection = Config::get('logins.database_connection');
//            $lifetime = Config::get('logins.lifetime');
//
//            return new LoginsSessionHandler(
//                app('db')->connection($connection),
//                'logins',
//                $lifetime,
//                $app
//            );
//        });

        // Register event subscribers
        Event::subscribe('ALajusticia\Logins\Listeners\SessionEventSubscriber');
        Event::subscribe('ALajusticia\Logins\Listeners\SanctumEventSubscriber');
        Event::listen(function (LoggedIn $event) {
            $event->authenticatable->notify(new NewLogin($event->context));
        });

        // Register Blade directives
        Blade::if('logins', function () {
            return method_exists(Request::user(), 'logins');
        });

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
        Auth::resolved(function ($auth) {
            $auth->extend('logins', function ($app, $name, array $config) {
                return tap($this->createGuard($name, $config), function ($guard) {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * Register the guard.
     */
    protected function createGuard(string $name, array $config): LoginsSessionGuard
    {
        $provider = Auth::createUserProvider($config['provider'] ?? null);

        $guard = new LoginsSessionGuard(
            $name,
            $provider,
            $this->app['session.store'],
        );

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        if (isset($config['remember'])) {
            $guard->setRememberDuration($config['remember']);
        }

        return $guard;
    }
}
