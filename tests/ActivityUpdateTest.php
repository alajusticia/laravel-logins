<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\CurrentLogin;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Events\TokenAuthenticated;

class ActivityUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The package TestCase migrates its own tables but not Sanctum's.
        $this->artisan('migrate', [
            '--path' => 'vendor/laravel/sanctum/database/migrations',
            '--realpath' => true,
        ])->run();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('logins.sanctum_token_tracking', true);
        $app['config']->set('cache.default', 'array');
    }

    private function tokenLogin(User $user, string $device = 'device'): array
    {
        $pat = $user->createToken($device)->accessToken;
        $login = $user->logins()->where('personal_access_token_id', $pat->getKey())->firstOrFail();

        return [$pat, $login];
    }

    private function authenticate(User $user, $pat): void
    {
        // A fresh request would rebuild these; the shared test app does not.
        app()->forgetInstance(CurrentLogin::class);
        $pat->setRelation('tokenable', $user->withAccessToken($pat));

        event(new TokenAuthenticated($pat));
    }

    public function test_interval_zero_updates_every_request(): void
    {
        config(['logins.activity_update.interval' => 0]);
        $user = User::factory()->create();
        [$pat, $login] = $this->tokenLogin($user);

        $this->authenticate($user, $pat);
        $first = $login->refresh()->last_activity_at;

        $this->travel(60)->seconds();
        $this->authenticate($user, $pat);

        $this->assertTrue($login->refresh()->last_activity_at->gt($first));
    }

    public function test_interval_throttles_within_window(): void
    {
        config(['logins.activity_update.interval' => 300]);
        $user = User::factory()->create();
        [$pat, $login] = $this->tokenLogin($user);

        $this->authenticate($user, $pat);
        $first = $login->refresh()->last_activity_at;

        $this->travel(60)->seconds();
        $this->authenticate($user, $pat);

        $this->assertTrue($login->refresh()->last_activity_at->equalTo($first));
    }

    public function test_updates_again_after_window(): void
    {
        config(['logins.activity_update.interval' => 300]);
        $user = User::factory()->create();
        [$pat, $login] = $this->tokenLogin($user);

        $this->authenticate($user, $pat);
        $first = $login->refresh()->last_activity_at;

        $this->travel(301)->seconds();
        $this->authenticate($user, $pat);

        $this->assertTrue($login->refresh()->last_activity_at->gt($first));
    }

    public function test_gate_is_per_token_not_per_user(): void
    {
        config(['logins.activity_update.interval' => 300]);
        $user = User::factory()->create();
        [$patA, $loginA] = $this->tokenLogin($user, 'device-a');
        [$patB, $loginB] = $this->tokenLogin($user, 'device-b');

        $this->travel(60)->seconds();
        $this->authenticate($user, $patA);
        $this->authenticate($user, $patB);

        $this->assertTrue(
            $loginB->refresh()->last_activity_at->equalTo($loginA->refresh()->last_activity_at)
        );
    }

    private function countLoginsQueries(\Closure $work): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $work();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $counts = ['select' => 0, 'write' => 0];
        foreach ($log as $q) {
            $sql = strtolower(trim($q['query']));
            if (! str_contains($sql, 'logins')) {
                continue;
            }
            if (str_starts_with($sql, 'select')) {
                $counts['select']++;
            } elseif (str_starts_with($sql, 'update') || str_starts_with($sql, 'insert')) {
                $counts['write']++;
            }
        }

        return $counts;
    }

    public function test_token_path_skips_both_queries_when_throttled(): void
    {
        config(['logins.activity_update.interval' => 300]);
        $user = User::factory()->create();
        [$pat] = $this->tokenLogin($user);

        // Move past creation time so the bump is a real UPDATE, not a same-value no-op.
        $this->travel(30)->seconds();
        $open = $this->countLoginsQueries(fn () => $this->authenticate($user, $pat));
        $this->assertSame(1, $open['select'], 'gate open: one SELECT to load the login');
        $this->assertSame(1, $open['write'], 'gate open: one UPDATE to bump last_activity_at');

        $this->travel(60)->seconds(); // still inside the 300s window

        $closed = $this->countLoginsQueries(fn () => $this->authenticate($user, $pat));
        $this->assertSame(0, $closed['select'], 'throttled: no SELECT');
        $this->assertSame(0, $closed['write'], 'throttled: no UPDATE');
    }

}
