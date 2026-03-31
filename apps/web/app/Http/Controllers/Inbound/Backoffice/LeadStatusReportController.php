<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\LeadStatusReportRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportHandler;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;

final class LeadStatusReportController extends Controller
{
    public function __invoke(
        LeadStatusReportRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        GetLeadStatusReportHandler $handler,
    ): View
    {
        $report = $handler(new GetLeadStatusReportQuery(
            leadCreatedAtRange: $request->resolveDateRange($dateRangeResolver),
        ));

        return view('admin.reports.lead-status', [
            'report' => $report,
            'filters' => $request->filters(),
            'presetOptions' => $request->presetOptions(),
        ]);
    }
}
