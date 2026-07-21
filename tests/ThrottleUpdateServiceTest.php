<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\ThrottleUpdateService;

class ThrottleUpdateServiceTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('cache.default', 'array');
    }

    public function test_interval_zero_runs_the_callback_every_time(): void
    {
        config(['logins.activity_update.interval' => 0]);
        $runs = 0;

        ThrottleUpdateService::update('k', function () use (&$runs) { $runs++; });
        ThrottleUpdateService::update('k', function () use (&$runs) { $runs++; });

        $this->assertSame(2, $runs);
    }

    public function test_throttles_within_the_window_per_key(): void
    {
        config(['logins.activity_update.interval' => 300]);
        $runs = 0;
        $cb = function () use (&$runs) { $runs++; };

        ThrottleUpdateService::update('a', $cb); // opens the gate
        ThrottleUpdateService::update('a', $cb); // throttled
        $this->assertSame(1, $runs);

        ThrottleUpdateService::update('b', $cb); // different key, independent gate
        $this->assertSame(2, $runs);

        $this->travel(301)->seconds();
        ThrottleUpdateService::update('a', $cb); // window elapsed
        $this->assertSame(3, $runs);
    }
}
