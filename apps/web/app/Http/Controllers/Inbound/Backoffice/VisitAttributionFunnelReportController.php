<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\VisitAttributionFunnelReportRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportQuery;

final class VisitAttributionFunnelReportController extends Controller
{
    public function __invoke(
        VisitAttributionFunnelReportRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        GetVisitAttributionFunnelReportHandler $handler,
    ): View {
        $report = $handler(new GetVisitAttributionFunnelReportQuery(
            reportPeriod: $request->resolveDateRange($dateRangeResolver),
        ));

        $rowClicksDrillQueries = [];
        $rowVisitsDrillQueries = [];

        foreach ($report->rows as $index => $row) {
            $rowClicksDrillQueries[$index] = $request->clicksBucketDrillQuery($row->source, $row->medium, $row->campaign);
            $rowVisitsDrillQueries[$index] = $request->visitsBucketDrillQuery($row->source, $row->medium, $row->campaign);
        }

        return view('admin.reports.visit-attribution-funnel', [
            'report' => $report,
            'filters' => $request->filters(),
            'presetOptions' => $request->presetOptions(),
            'summaryClicksDrillQuery' => $request->clicksDrillQuery(),
            'summaryVisitsDrillQuery' => $request->visitsDrillQuery(),
            'rowClicksDrillQueries' => $rowClicksDrillQueries,
            'rowVisitsDrillQueries' => $rowVisitsDrillQueries,
        ]);
    }
}
