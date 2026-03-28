<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetLeadTimeline;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineHandler;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineQuery;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineEventView;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineView;
use Inbound\Domain\Lead\LeadId;
use PHPUnit\Framework\TestCase;

final class GetLeadTimelineHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_timeline_view(): void
    {
        $query = new GetLeadTimelineQuery(new LeadId('lead-123'));
        $expectedView = new LeadTimelineView(
            leadId: 'lead-123',
            events: [
                new LeadTimelineEventView(
                    type: 'lead_created',
                    occurredAt: new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
                    title: 'Лід створено',
                    description: 'Форма',
                    origin: 'form',
                    originLabel: 'Форма',
                ),
                new LeadTimelineEventView(
                    type: 'lead_note',
                    occurredAt: new DateTimeImmutable('2026-03-28T12:30:00+02:00'),
                    title: 'Додано нотатку',
                    description: 'Need to call back tomorrow.',
                    authorId: 42,
                ),
            ],
        );

        $readModel = new RecordingLeadTimelineReadModel($expectedView);
        $handler = new GetLeadTimelineHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame('lead-123', $result->leadId);
        $this->assertCount(2, $result->events);
        $this->assertSame('lead_note', $result->events[1]->type);
    }
}

final class RecordingLeadTimelineReadModel implements LeadTimelineReadModel
{
    public ?GetLeadTimelineQuery $receivedQuery = null;

    public function __construct(
        private readonly LeadTimelineView $view,
    ) {
    }

    public function __invoke(GetLeadTimelineQuery $query): LeadTimelineView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
