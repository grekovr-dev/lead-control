<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetLeadDetails;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\ActivitySummaryView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\AttributionSnapshotView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsQuery;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadCoreView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadVisitSummaryView;
use Inbound\Domain\Lead\LeadId;
use PHPUnit\Framework\TestCase;

final class GetLeadDetailsHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_details_view(): void
    {
        $query = new GetLeadDetailsQuery(new LeadId('lead-123'));
        $expectedView = new LeadDetailsView(
            lead: new LeadCoreView(
                leadId: 'lead-123',
                visitorId: 'visitor-456',
                visitId: 'visit-789',
                name: 'John Doe',
                phone: '+380501112233',
                status: 'new',
                statusLabel: 'Новый',
                origin: 'form',
                createdAt: new DateTimeImmutable('2026-03-26T12:00:00+02:00'),
                attribution: new AttributionSnapshotView(
                    source: 'google',
                    medium: 'cpc',
                    campaign: 'spring-sale',
                    content: null,
                    term: null,
                    gclid: null,
                    fbclid: null,
                    msclkid: null,
                ),
            ),
            visit: new LeadVisitSummaryView(
                visitId: 'visit-789',
                visitorId: 'visitor-456',
                startedAt: new DateTimeImmutable('2026-03-26T11:40:00+02:00'),
                lastTouchedAt: new DateTimeImmutable('2026-03-26T11:58:00+02:00'),
                firstAttribution: new AttributionSnapshotView(
                    source: 'google',
                    medium: 'cpc',
                    campaign: 'spring-sale',
                    content: null,
                    term: null,
                    gclid: null,
                    fbclid: null,
                    msclkid: null,
                ),
                lastAttribution: new AttributionSnapshotView(
                    source: 'google',
                    medium: 'remarketing',
                    campaign: 'spring-sale',
                    content: null,
                    term: null,
                    gclid: null,
                    fbclid: null,
                    msclkid: null,
                ),
            ),
            preLeadTouchSummary: new ActivitySummaryView(
                count: 3,
                lastOccurredAt: new DateTimeImmutable('2026-03-26T11:59:00+02:00'),
            ),
            preLeadVisitorClickSummary: new ActivitySummaryView(
                count: 2,
                lastOccurredAt: new DateTimeImmutable('2026-03-26T11:50:00+02:00'),
            ),
        );

        $readModel = new RecordingLeadDetailsReadModel($expectedView);
        $handler = new GetLeadDetailsHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame('lead-123', $result->lead->leadId);
        $this->assertSame(3, $result->preLeadTouchSummary->count);
        $this->assertSame(2, $result->preLeadVisitorClickSummary->count);
    }
}

final class RecordingLeadDetailsReadModel implements LeadDetailsReadModel
{
    public ?GetLeadDetailsQuery $receivedQuery = null;

    public function __construct(
        private readonly LeadDetailsView $view,
    ) {
    }

    public function __invoke(GetLeadDetailsQuery $query): LeadDetailsView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
