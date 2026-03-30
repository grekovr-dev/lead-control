<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\ListLeads;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadListItemView;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListView;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsHandler;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsQuery;
use Inbound\Domain\Lead\LeadStatus;
use PHPUnit\Framework\TestCase;

final class ListLeadsHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_a_paginated_view(): void
    {
        $query = new ListLeadsQuery(
            status: LeadStatus::NEW,
            origin: 'form',
            attributionSource: 'google',
            attributionMedium: 'cpc',
            page: 2,
            perPage: 15,
        );

        $expectedView = new LeadsListView(
            currentPage: 2,
            perPage: 15,
            total: 31,
            lastPage: 3,
            items: [
                new LeadListItemView(
                    leadId: 'lead-1',
                    shortLeadId: 'lead-1',
                    visitorId: 'visitor-1',
                    visitId: 'visit-1',
                    name: 'John Doe',
                    phone: '+380501112233',
                    status: 'new',
                    statusLabel: 'Новий',
                    origin: 'form',
                    originLabel: 'Форма',
                    attributionSource: 'google',
                    attributionMedium: 'cpc',
                    createdAt: new DateTimeImmutable('2026-03-26T12:00:00+02:00'),
                ),
                new LeadListItemView(
                    leadId: 'lead-2',
                    shortLeadId: 'lead-2',
                    visitorId: 'visitor-2',
                    visitId: 'visit-2',
                    name: null,
                    phone: null,
                    status: 'new',
                    statusLabel: 'Новий',
                    origin: 'form',
                    originLabel: 'Форма',
                    attributionSource: 'google',
                    attributionMedium: 'cpc',
                    createdAt: new DateTimeImmutable('2026-03-26T11:00:00+02:00'),
                ),
            ],
        );

        $readModel = new RecordingLeadsListReadModel($expectedView);
        $handler = new ListLeadsHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(2, $result->currentPage);
        $this->assertSame(31, $result->total);
        $this->assertCount(2, $result->items);
    }
}

final class RecordingLeadsListReadModel implements LeadsListReadModel
{
    public ?ListLeadsQuery $receivedQuery = null;

    public function __construct(
        private readonly LeadsListView $view,
    ) {
    }

    public function __invoke(ListLeadsQuery $query): LeadsListView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
