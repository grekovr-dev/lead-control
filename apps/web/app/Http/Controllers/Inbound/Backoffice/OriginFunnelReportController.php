<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportQuery;

final class OriginFunnelReportController extends Controller
{
    public function __invoke(GetOriginFunnelReportHandler $handler): View
    {
        return view('admin.reports.origin-funnel', [
            'report' => $handler(new GetOriginFunnelReportQuery),
        ]);
    }
}
