<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Infrastructure\Notifications\Telegram;

use Illuminate\Support\Facades\Http;
use Inbound\Application\Notifications\Telegram\TelegramClientException;
use Inbound\Infrastructure\Notifications\Telegram\LaravelHttpTelegramClient;
use Tests\TestCase;

final class LaravelHttpTelegramClientTest extends TestCase
{
    public function test_it_sends_a_plain_text_message_to_the_configured_chat(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 42,
                ],
            ]),
        ]);

        $client = new LaravelHttpTelegramClient(
            botToken: 'test-token',
            baseUrl: 'https://api.telegram.org',
            timeoutSeconds: 10,
        );

        $client->sendMessage('-1001234567890', 'New lead created');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '-1001234567890'
                && $request['text'] === 'New lead created';
        });
    }

    public function test_it_throws_when_http_request_fails(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([], 500),
        ]);

        $client = new LaravelHttpTelegramClient(
            botToken: 'test-token',
            baseUrl: 'https://api.telegram.org',
            timeoutSeconds: 10,
        );

        $this->expectException(TelegramClientException::class);
        $this->expectExceptionMessage('Telegram sendMessage request failed with status 500.');

        $client->sendMessage('-1001234567890', 'New lead created');
    }

    public function test_it_throws_when_telegram_rejects_the_request(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: chat not found',
            ], 200),
        ]);

        $client = new LaravelHttpTelegramClient(
            botToken: 'test-token',
            baseUrl: 'https://api.telegram.org',
            timeoutSeconds: 10,
        );

        $this->expectException(TelegramClientException::class);
        $this->expectExceptionMessage('Telegram sendMessage request was rejected: Bad Request: chat not found');

        $client->sendMessage('-1001234567890', 'New lead created');
    }
}
