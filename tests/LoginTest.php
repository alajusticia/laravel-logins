<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Models\Login;
use Carbon\Carbon;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setLocale('en');
    }

    protected function tearDown(): void
    {
        Carbon::setLocale('en');

        parent::tearDown();
    }

    public function test_last_active_uses_throttled_minutes_when_inside_the_interval(): void
    {
        config(['logins.activity_update.interval' => 300]);

        $login = new Login([
            'last_activity_at' => now()->subMinute(),
        ]);

        $this->assertSame('less than 5 minutes ago', $login->last_active);
    }

    public function test_last_active_rounds_throttled_minutes_up(): void
    {
        config(['logins.activity_update.interval' => 90]);

        $login = new Login([
            'last_activity_at' => now()->subMinute(),
        ]);

        $this->assertSame('less than 2 minutes ago', $login->last_active);
    }

    public function test_last_active_uses_throttled_seconds_for_sub_minute_intervals(): void
    {
        config(['logins.activity_update.interval' => 45]);

        $login = new Login([
            'last_activity_at' => now()->subSeconds(30),
        ]);

        $this->assertSame('less than 45 seconds ago', $login->last_active);
    }

    public function test_last_active_uses_carbon_locale_for_throttled_rendering(): void
    {
        Carbon::setLocale('es');
        config(['logins.activity_update.interval' => 300]);

        $login = new Login([
            'last_activity_at' => now()->subMinute(),
        ]);

        $this->assertSame('hace menos de 5 minutos', $login->last_active);
    }

    public function test_last_active_uses_exact_rendering_outside_the_throttled_interval(): void
    {
        config(['logins.activity_update.interval' => 300]);

        $login = new Login([
            'last_activity_at' => now()->subMinutes(6),
        ]);

        $this->assertSame('6 minutes ago', $login->last_active);
    }
}
