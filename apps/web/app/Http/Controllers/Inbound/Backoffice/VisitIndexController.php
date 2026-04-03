<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListVisitsRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsHandler;

final class VisitIndexController extends Controller
{
    public function __invoke(
        ListVisitsRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        ListVisitsHandler $handler,
    ): View {
        $visits = $handler($request->toQuery($dateRangeResolver));

        return view('admin.visits.index', [
            'visits' => $visits,
            'drillContextItems' => $request->drillContextItems($dateRangeResolver),
            'paginationQuery' => $request->paginationQuery($dateRangeResolver),
        ]);
    }
}
