<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\VisitorAcquisitionFunnelReportRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportHandler;

final class VisitorAcquisitionFunnelReportController extends Controller
{
    public function __invoke(
        VisitorAcquisitionFunnelReportRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        GetVisitorAcquisitionFunnelReportHandler $handler,
    ): View {
        $report = $handler($request->toQuery($dateRangeResolver));

        return view('admin.reports.visitor-acquisition-funnel', [
            'report' => $report,
            'filters' => $request->filters(),
            'presetOptions' => $request->presetOptions(),
        ]);
    }
}
