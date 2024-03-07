<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Expirable\ExpirableServiceProvider;
use ALajusticia\Logins\LoginsServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\SanctumServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
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
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ExpirableServiceProvider::class,
            LoginsServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'logins',
                'model' => User::class,
            ],
        ]);
    }

    protected function setRoutes()
    {
        Route::prefix('api')->middleware(['api'])->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            })->middleware('auth:sanctum');
        });
    }
}
