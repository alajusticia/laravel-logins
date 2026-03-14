<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Http\Controllers\LoginsController;
use ALajusticia\Logins\Http\Resources\LoginResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class PackageRoutesEnabledTest extends TestCase
{
    protected function shouldRegisterPackageRoutes(): bool
    {
        return true;
    }

    public function test_package_routes_are_registered_when_opted_in(): void
    {
        $this->assertTrue(Route::has('logins.index'));
        $this->assertTrue(Route::has('logins.destroy'));
        $this->assertTrue(Route::has('logins.destroyOthers'));
        $this->assertTrue(Route::has('logins.destroyAll'));
    }

    public function test_index_returns_login_resources_for_the_current_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = $this->app['session.store'];
        $session->setId(str_repeat('b', 40));
        $session->start();

        $user->logins()->create([
            'session_id' => $session->getId(),
            'last_activity_at' => now(),
        ]);

        $request = Request::create('/api/logins', 'GET');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        $this->app->instance('request', $request);

        $resource = $this->app->make(LoginsController::class)->index($request);
        $payload = $resource->response($request)->getData(true);

        $this->assertInstanceOf(LoginResource::class, $resource->collection->first());
        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);
        $this->assertArrayNotHasKey('disconnect_url', $payload['data'][0]);
        $this->assertArrayNotHasKey('disconnectAllUrl', $payload['data'][0]);
    }

    public function test_destroy_route_revokes_the_selected_login(): void
    {
        $user = User::factory()->create();
        $this->withSession([]);
        $this->actingAs($user);

        $currentLogin = $user->logins()->create([
            'session_id' => $this->app['session']->getId(),
            'last_activity_at' => now(),
        ]);

        $otherLogin = $user->logins()->create([
            'session_id' => 'another-session',
            'last_activity_at' => now()->subMinute(),
        ]);

        $request = Request::create("/api/logins/{$otherLogin->getKey()}", 'DELETE', [
            'password' => 'password',
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $user);

        $this->app->instance('request', $request);

        $response = $this->app->make(LoginsController::class)->destroy($request, $otherLogin->getKey());

        $this->assertSame(204, $response->getStatusCode());

        $this->assertDatabaseHas('logins', [
            'id' => $currentLogin->getKey(),
            'deleted_at' => null,
        ]);

        $this->assertSoftDeleted('logins', [
            'id' => $otherLogin->getKey(),
        ]);
    }

    public function test_destroy_others_route_revokes_all_other_logins(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = $this->app['session.store'];
        $session->setId(str_repeat('a', 40));
        $session->start();

        $currentLogin = $user->logins()->create([
            'session_id' => $session->getId(),
            'last_activity_at' => now(),
        ]);

        $otherLogin = $user->logins()->create([
            'session_id' => 'another-session',
            'last_activity_at' => now()->subMinute(),
        ]);

        $request = Request::create('/api/logins', 'DELETE', [
            'password' => 'password',
        ]);
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        $this->app->instance('request', $request);

        $response = $this->app->make(LoginsController::class)->destroyOthers($request);

        $this->assertSame(204, $response->getStatusCode());

        $this->assertDatabaseHas('logins', [
            'id' => $currentLogin->getKey(),
            'deleted_at' => null,
        ]);

        $this->assertSoftDeleted('logins', [
            'id' => $otherLogin->getKey(),
        ]);
    }
}
