<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Expirable\ExpirableServiceProvider;
use ALajusticia\Logins\Logins;
use ALajusticia\Logins\LoginsServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\SanctumServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate')->run();

        $this->setRoutes();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ExpirableServiceProvider::class,
            LoginsServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        Logins::registerRoutes($this->shouldRegisterRoutes());

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');

        $app['config']->set('auth.guards', [
            'web' => [
                'driver' => 'logins',
                'provider' => 'users',
            ],
        ]);

        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'logins',
                'model' => User::class,
            ],
        ]);
    }

    /**
     * Determine if package-managed routes should be registered for the test application.
     */
    protected function shouldRegisterRoutes(): bool
    {
        return false;
    }

    /**
     * Set up routes
     */
    protected function setRoutes(): void
    {
        Route::prefix('api')->middleware(['api'])->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            })->middleware('auth:sanctum');
        });
    }
}
