<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Notifications\Telegram;

use Illuminate\Support\Facades\Http;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Application\Notifications\Telegram\TelegramClientException;

final readonly class LaravelHttpTelegramClient implements TelegramClient
{
    public function __construct(
        private string $botToken,
        private string $baseUrl = 'https://api.telegram.org',
        private int $timeoutSeconds = 10,
    ) {}

    public function sendMessage(string $chatId, string $text): void
    {
        $response = Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->timeout($this->timeoutSeconds)
            ->asJson()
            ->post(sprintf('/bot%s/sendMessage', $this->botToken), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        if (! $response->successful()) {
            throw new TelegramClientException(sprintf(
                'Telegram sendMessage request failed with status %d.',
                $response->status(),
            ));
        }

        if ($response->json('ok') !== true) {
            throw new TelegramClientException(sprintf(
                'Telegram sendMessage request was rejected: %s',
                $this->responseDescription($response->json('description')),
            ));
        }
    }

    private function responseDescription(mixed $description): string
    {
        return is_string($description) && $description !== ''
            ? $description
            : 'Unknown Telegram API error.';
    }
}
