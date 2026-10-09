<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Events\LoggedIn;
use ALajusticia\Logins\Logins;
use ALajusticia\Logins\Models\Login;
use ALajusticia\Logins\Notifications\NewLogin;
use ALajusticia\Logins\RequestContext;
use ALajusticia\Logins\Support\SanctumForeignKey;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumDevicesTest extends TestCase
{
    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('logins.sanctum_token_tracking', true);
    }

    protected function tearDown(): void
    {
        Logins::describeDeviceUsing(null);

        parent::tearDown();
    }

    public function test_the_login_of_a_token_is_deleted_with_the_token_by_the_migrations(): void
    {
        // Enforced by default in Laravel applications, not in this test database
        Schema::enableForeignKeyConstraints();

        $user = User::factory()->create();
        $token = $user->createToken('mobile');
        $this->assertSame(1, $user->logins()->count());

        // Deleted in bulk, without model events (password change, pruning of expired tokens…)
        PersonalAccessToken::query()->whereKey($token->accessToken->getKey())->delete();

        $this->assertSame(0, Login::withTrashed()->withExpired()->count());
        $this->assertTrue(collect(Schema::getIndexes('logins'))->contains(fn (array $index) => $index['columns'] === ['personal_access_token_id']));
    }

    public function test_the_foreign_key_is_added_after_migrations_creating_the_tokens_table_later(): void
    {
        // As when the application's tokens table migration runs after the package's one
        SanctumForeignKey::drop();
        $this->assertFalse(SanctumForeignKey::exists());

        $user = User::factory()->create();
        $kept = $user->createToken('mobile');
        DB::table('logins')->insert([
            'authenticatable_type' => $user->getMorphClass(), 'authenticatable_id' => $user->getKey(),
            'personal_access_token_id' => 999, 'last_activity_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        event(new MigrationsEnded('up'));

        $this->assertTrue(SanctumForeignKey::exists());
        $this->assertSame([$kept->accessToken->getKey()], Login::withTrashed()->withExpired()->pluck('personal_access_token_id')->all());

        // Nothing to do when it already exists
        event(new MigrationsEnded('up'));
        $this->assertTrue(SanctumForeignKey::exists());
    }

    public function test_a_described_device_replaces_the_parsed_values_in_the_login_and_its_event(): void
    {
        request()->headers->set('User-Agent', 'okhttp/4.12.0');
        request()->attributes->set('device', ['device_type' => 'mobile', 'device' => 'Google Pixel 9', 'platform' => 'Android']);
        Logins::describeDeviceUsing(fn (RequestContext $context) => [
            ...request()->attributes->get('device'),
            'browser' => $context->tokenName(),
            'unknown' => 'ignored',
        ]);
        $events = [];
        Event::listen(function (LoggedIn $event) use (&$events) {
            $events[] = $event->context->toArray();
        });

        $token = User::factory()->create()->createToken('My App');

        $login = Login::where('personal_access_token_id', $token->accessToken->getKey())->firstOrFail();
        $this->assertSame(['mobile', 'Google Pixel 9', 'Android', 'My App'], [$login->device_type, $login->device, $login->platform, $login->browser]);
        $this->assertSame('Google Pixel 9 - Android - My App', $login->label);
        $this->assertCount(1, $events);
        $this->assertSame(['mobile', 'Google Pixel 9', 'Android', 'My App'], [$events[0]['device_type'], $events[0]['device'], $events[0]['platform'], $events[0]['browser']]);
        $this->assertArrayNotHasKey('unknown', $events[0]);
    }

    public function test_values_not_described_keep_the_parsed_ones(): void
    {
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36');
        Logins::describeDeviceUsing(fn () => ['device' => 'Work laptop']);

        $context = new RequestContext;

        $this->assertSame('Work laptop', $context->device());
        $this->assertSame('desktop', $context->deviceType());
        $this->assertNotEmpty($context->platform());
        $this->assertNotEmpty($context->browser());

        Logins::describeDeviceUsing(fn () => null);
        $this->assertSame((new RequestContext)->parser()->getDevice(), (new RequestContext)->device());
    }

    public function test_token_logins_can_be_created_without_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['created_at' => now()->subHour()]);
        $user->createToken('first');

        // The property cannot apply to tokens: their login is attached to the model as retrieved from the database
        Logins::withoutNotifications(fn () => $user->createToken('silent'));

        Notification::assertSentToTimes($user, NewLogin::class, 1);
        $this->assertTrue(Logins::notificationsEnabled());
    }
}
