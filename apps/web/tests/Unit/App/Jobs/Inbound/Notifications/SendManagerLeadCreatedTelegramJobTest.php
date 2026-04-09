<?php

declare(strict_types=1);

namespace Tests\Unit\App\Jobs\Inbound\Notifications;

use App\Jobs\Inbound\Notifications\SendManagerLeadCreatedTelegramJob;
use DateTimeImmutable;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationHandler;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationQuery;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationReadModel;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationView;
use Tests\TestCase;

final class SendManagerLeadCreatedTelegramJobTest extends TestCase
{
    public function test_it_exposes_the_lead_id(): void
    {
        $job = new SendManagerLeadCreatedTelegramJob('lead-123');

        $this->assertSame('lead-123', $job->leadId);
    }

    public function test_it_loads_the_notification_payload_and_sends_it_to_telegram(): void
    {
        config()->set('services.telegram.manager_chat_id', '-1001234567890');
        config()->set('services.telegram.lead_url_base', 'https://dobri-steli.kiev.ua/admin/leads');

        $job = new SendManagerLeadCreatedTelegramJob('lead-123');
        $handler = new GetManagerLeadNotificationHandler(
            new RecordingManagerLeadNotificationReadModel(
                new ManagerLeadNotificationView(
                    leadId: 'lead-123',
                    name: 'John Doe',
                    phone: '+380501112233',
                    origin: 'form',
                    landingUrl: 'https://example.com/landing',
                    createdAt: new DateTimeImmutable('2026-04-09 13:45:00'),
                ),
            ),
        );
        $telegramClient = $this->createMock(TelegramClient::class);
        $telegramClient->expects($this->once())
            ->method('sendMessage')
            ->with(
                '-1001234567890',
                $this->callback(static function (string $message): bool {
                    return str_contains($message, '<a href="https://dobri-steli.kiev.ua/admin/leads/lead-123"')
                        && str_contains($message, 'Новий лід 09.04.2026 13:45</a>')
                        && str_contains($message, 'Джерело: Форма')
                        && str_contains($message, "Ім'я: John Doe")
                        && str_contains($message, 'Телефон: +380501112233')
                        && ! str_contains($message, 'ID: lead-123')
                        && ! str_contains($message, 'Лендінг:');
                }),
                'HTML',
                true,
            );

        $job->handle($handler, $telegramClient);
    }
}

final class RecordingManagerLeadNotificationReadModel implements ManagerLeadNotificationReadModel
{
    public ?GetManagerLeadNotificationQuery $receivedQuery = null;

    public function __construct(
        private readonly ManagerLeadNotificationView $view,
    ) {}

    public function __invoke(GetManagerLeadNotificationQuery $query): ManagerLeadNotificationView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
