<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetFunnelTrends;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendPointView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsReadModel;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsHandler;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsQuery;
use PHPUnit\Framework\TestCase;

final class GetFunnelTrendsHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_trends_view(): void
    {
        $query = new GetFunnelTrendsQuery(
            dateFrom: new DateTimeImmutable('2026-03-01 00:00:00'),
            dateTo: new DateTimeImmutable('2026-03-31 00:00:00'),
        );

        $expectedView = new FunnelTrendsView(
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            clicksCount: 10,
            visitsCount: 8,
            leadsCount: 3,
            clicksToLeadsConversionRate: 30.0,
            visitsToLeadsConversionRate: 37.5,
            rows: [
                new FunnelTrendPointView(
                    date: new DateTimeImmutable('2026-03-10 00:00:00'),
                    clicksCount: 4,
                    visitsCount: 3,
                    leadsCount: 1,
                    clicksToLeadsConversionRate: 25.0,
                    visitsToLeadsConversionRate: 33.33,
                ),
                new FunnelTrendPointView(
                    date: new DateTimeImmutable('2026-03-11 00:00:00'),
                    clicksCount: 6,
                    visitsCount: 5,
                    leadsCount: 2,
                    clicksToLeadsConversionRate: 33.33,
                    visitsToLeadsConversionRate: 40.0,
                ),
            ],
        );

        $readModel = new RecordingFunnelTrendsReadModel($expectedView);
        $handler = new GetFunnelTrendsHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(10, $result->clicksCount);
        $this->assertSame(30.0, $result->clicksToLeadsConversionRate);
        $this->assertCount(2, $result->rows);
    }
}

final class RecordingFunnelTrendsReadModel implements FunnelTrendsReadModel
{
    public ?GetFunnelTrendsQuery $receivedQuery = null;

    public function __construct(
        private readonly FunnelTrendsView $view,
    ) {
    }

    public function __invoke(GetFunnelTrendsQuery $query): FunnelTrendsView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
