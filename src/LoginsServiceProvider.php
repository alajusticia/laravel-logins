<?php

namespace ALajusticia\Logins;

use ALajusticia\Logins\Commands\Install;
use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Notifications\NewLogin;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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

        // Register custom Eloquent user provider
        Auth::provider('logins', function (Application $app, array $config) {
            return new LoginsUserProvider($app['hash'], $config['model']);
        });

        // Register event subscribers
        Event::subscribe('ALajusticia\Logins\Listeners\AuthEventSubscriber');
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
}
