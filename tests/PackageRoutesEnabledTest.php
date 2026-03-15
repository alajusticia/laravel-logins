<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Http\Controllers\LoginsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class PackageRoutesEnabledTest extends TestCase
{
    protected function shouldRegisterRoutes(): bool
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

    public function test_index_route_works_with_session_authentication(): void
    {
        $user = User::factory()->create();
        $this->withSession([]);
        $this->actingAs($user);

        $user->logins()->create([
            'session_id' => $this->app['session.store']->getId(),
            'last_activity_at' => now(),
        ]);

        $this->getJson('/api/logins')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertCookie('XSRF-TOKEN')
            ->assertJsonMissingPath('data.0.disconnect_url')
            ->assertJsonMissingPath('data.0.disconnectAllUrl');
    }

    public function test_package_routes_use_web_authentication_middleware(): void
    {
        $middleware = Route::getRoutes()->getByName('logins.index')->gatherMiddleware();

        $this->assertContains('web', $middleware);
        $this->assertContains('auth', $middleware);
        $this->assertNotContains('api', $middleware);
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

        $this->deleteJson("/api/logins/{$otherLogin->getKey()}", [
            'password' => 'password',
        ])->assertNoContent();

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

        $request = Request::create('/api/logins/others', 'DELETE', [
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
