<?php

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewHandler;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewQuery;

class DashboardController extends Controller
{
    public function __invoke(GetDashboardOverviewHandler $handler): View
    {
        $overview = $handler(new GetDashboardOverviewQuery());

        return view('admin.dashboard.index', [
            'overview' => $overview,
        ]);
    }
}
