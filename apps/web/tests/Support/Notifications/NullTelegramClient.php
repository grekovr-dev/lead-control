<?php

declare(strict_types=1);

namespace Tests\Support\Notifications;

use Inbound\Application\Notifications\Telegram\TelegramClient;

final class NullTelegramClient implements TelegramClient
{
    public function sendMessage(
        string $chatId,
        string $text,
        ?string $parseMode = null,
        bool $disableWebPagePreview = false,
    ): void {}
}
