<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Logins;
use Illuminate\Http\Request;

class LoginsTest extends TestCase
{
    public function test_default_ip_address_ignores_untrusted_cloudflare_headers(): void
    {
        $request = Request::create('/', server: [
            'REMOTE_ADDR' => '192.0.2.10',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.42',
        ]);

        $this->app->instance('request', $request);
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.42';

        try {
            $this->assertSame('192.0.2.10', Logins::ipAddress());
        } finally {
            unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
    }

    public function test_default_ip_address_uses_the_laravel_trusted_proxy_chain(): void
    {
        Request::setTrustedProxies(
            ['173.245.48.0/20'],
            Request::HEADER_X_FORWARDED_FOR,
        );

        $request = Request::create('/', server: [
            'REMOTE_ADDR' => '173.245.48.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.42',
        ]);

        $this->app->instance('request', $request);
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.99';

        try {
            $this->assertSame('203.0.113.42', Logins::ipAddress());
        } finally {
            unset($_SERVER['HTTP_CF_CONNECTING_IP']);
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        }
    }

    public function test_default_ip_address_can_be_null(): void
    {
        $request = Request::create('/');
        $request->server->remove('REMOTE_ADDR');

        $this->app->instance('request', $request);

        $this->assertNull(Logins::ipAddress());
    }
}
