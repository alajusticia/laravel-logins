<?php

namespace ALajusticia\Logins\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SessionTest extends TestCase
{
    public function test_auth_with_session(): void
    {
        // Create user
        $user = User::factory()->create();

        // Authenticate user to dispatch login event
        Auth::login($user);

        // Check that current login exists
        $currentLogin = $user->logins()
            ->where('session_id', session()->getId())
            ->first();

        $this->assertNotNull($currentLogin);
    }

    public function test_auth_with_session_truncates_long_user_agent(): void
    {
        $user = User::factory()->create();
        Config::set('logins.user_agent_max_length', 64);

        $userAgent = 'Mozilla/5.0 '.str_repeat('[FBAN/FBIOS;FBAV/468.0.0.44.108]', 12);

        request()->headers->set('User-Agent', $userAgent);

        Auth::login($user);

        $currentLogin = $user->logins()
            ->where('session_id', session()->getId())
            ->first();

        $this->assertNotNull($currentLogin);
        $this->assertSame(substr($userAgent, 0, 64), $currentLogin->user_agent);
    }

    public function test_auth_with_session_truncates_user_agent_on_multibyte_boundary(): void
    {
        $user = User::factory()->create();
        Config::set('logins.user_agent_max_length', 6);

        $userAgent = 'UA-ééé';

        request()->headers->set('User-Agent', $userAgent);

        Auth::login($user);

        $currentLogin = $user->logins()
            ->where('session_id', session()->getId())
            ->first();

        $this->assertNotNull($currentLogin);
        $this->assertSame('UA-é', $currentLogin->user_agent);
        $this->assertLessThanOrEqual(6, strlen($currentLogin->user_agent));
    }

    public function test_session_tracking_failure_does_not_block_login(): void
    {
        $user = User::factory()->create();
        $handler = $this->fakeExceptionHandler();

        Schema::drop('logins');

        $this->assertDoesntThrow(function () use ($user) {
            Auth::login($user);
        });

        $this->assertTrue(Auth::check());
        $this->assertCount(0, $handler->reported);
    }
}
