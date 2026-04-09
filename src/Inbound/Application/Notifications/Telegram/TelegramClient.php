<?php

declare(strict_types=1);

namespace Inbound\Application\Notifications\Telegram;

interface TelegramClient
{
    /**
     * @throws TelegramClientException
     */
    public function sendMessage(
        string $chatId,
        string $text,
        ?string $parseMode = null,
        bool $disableWebPagePreview = false,
    ): void;
}
