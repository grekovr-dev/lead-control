<?php

declare(strict_types=1);

namespace App\Jobs\Inbound\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationHandler;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationQuery;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationView;
use Inbound\Domain\Lead\LeadId;
use RuntimeException;

final class SendManagerLeadCreatedTelegramJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $leadId,
    ) {}

    public function handle(
        GetManagerLeadNotificationHandler $getManagerLeadNotification,
        TelegramClient $telegramClient,
    ): void {
        $managerChatId = config('services.telegram.manager_chat_id');

        if (! is_string($managerChatId) || $managerChatId === '') {
            throw new RuntimeException('Telegram manager chat id is not configured.');
        }

        $view = $getManagerLeadNotification(
            new GetManagerLeadNotificationQuery(new LeadId($this->leadId)),
        );

        $telegramClient->sendMessage($managerChatId, $this->messageText($view));
    }

    private function messageText(ManagerLeadNotificationView $view): string
    {
        $lines = [
            'Новий лід',
            'ID: '.$view->leadId,
            'Дата: '.$view->createdAt->format('d.m.Y H:i'),
            'Джерело: '.$this->originLabel($view->origin),
        ];

        if ($view->name !== null && $view->name !== '') {
            $lines[] = "Ім'я: ".$view->name;
        }

        if ($view->phone !== null && $view->phone !== '') {
            $lines[] = 'Телефон: '.$view->phone;
        }

        if ($view->landingUrl !== null && $view->landingUrl !== '') {
            $lines[] = 'Лендінг: '.$view->landingUrl;
        }

        return implode("\n", $lines);
    }

    private function originLabel(string $origin): string
    {
        return match ($origin) {
            'form' => 'Форма',
            'phone_click' => 'Клік по телефону',
            'messenger_click' => 'Клік по месенджеру',
            default => $origin,
        };
    }
}
