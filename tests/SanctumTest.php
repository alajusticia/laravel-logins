<?php

namespace ALajusticia\Logins\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SanctumTest extends TestCase
{
    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('logins.sanctum_token_tracking', true);
    }

    public function test_sanctum_token_creation_truncates_long_user_agent(): void
    {
        $user = User::factory()->create();
        Config::set('logins.user_agent_max_length', 64);

        $userAgent = 'Mozilla/5.0 '.str_repeat('[FBAN/FBIOS;FBAV/468.0.0.44.108]', 12);

        request()->headers->set('User-Agent', $userAgent);

        $token = $user->createToken('mobile');

        $login = $user->logins()
            ->where('personal_access_token_id', $token->accessToken->getKey())
            ->first();

        $this->assertNotNull($login);
        $this->assertSame(substr($userAgent, 0, 64), $login->user_agent);
    }

    public function test_sanctum_tracking_failure_does_not_block_token_creation(): void
    {
        $user = User::factory()->create();
        $handler = $this->fakeExceptionHandler();
        $token = null;

        Schema::drop('logins');

        $this->assertDoesntThrow(function () use ($user, &$token) {
            $token = $user->createToken('mobile');
        });

        $this->assertNotNull($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->getKey(),
        ]);
        $this->assertCount(0, $handler->reported);
    }
}
