<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\FunnelTrendsRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsHandler;

final class FunnelTrendsController extends Controller
{
    public function __invoke(
        FunnelTrendsRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        GetFunnelTrendsHandler $handler,
    ): View {
        $report = $handler($request->toQuery($dateRangeResolver));

        return view('admin.reports.funnel-trends', [
            'report' => $report,
            'filters' => $request->filters(),
            'presetOptions' => $request->presetOptions(),
        ]);
    }
}
