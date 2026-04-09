<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Notifications\GetManagerLeadNotification;

use DateTimeImmutable;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationHandler;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationQuery;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationReadModel;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationView;
use Inbound\Domain\Lead\LeadId;
use PHPUnit\Framework\TestCase;

final class GetManagerLeadNotificationHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_notification_view(): void
    {
        $query = new GetManagerLeadNotificationQuery(new LeadId('lead-123'));
        $expectedView = new ManagerLeadNotificationView(
            leadId: 'lead-123',
            name: 'John Doe',
            phone: '+380501112233',
            origin: 'form',
            landingUrl: 'https://example.com/landing',
            createdAt: new DateTimeImmutable('2026-04-08T09:00:00+03:00'),
        );

        $readModel = new RecordingManagerLeadNotificationReadModel($expectedView);
        $handler = new GetManagerLeadNotificationHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame('lead-123', $result->leadId);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('https://example.com/landing', $result->landingUrl);
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
