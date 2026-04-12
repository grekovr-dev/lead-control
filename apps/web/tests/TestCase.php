<?php

namespace Tests;

use App\Models\User;
use App\Support\BackofficePermissions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Infrastructure\Notifications\Telegram\NullTelegramClient;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (str_starts_with(static::class, 'Tests\\Feature\\')) {
            $this->app->instance(TelegramClient::class, new NullTelegramClient);
        }

        if (str_starts_with(static::class, 'Tests\\Feature\\App\\Http\\Controllers\\Inbound\\Backoffice\\')) {
            $this->actingAs(User::factory()->make());
            $this->withSession([
                'backoffice_role_name' => 'admin',
                'backoffice_permissions' => BackofficePermissions::all(),
            ]);
        }
    }
}
