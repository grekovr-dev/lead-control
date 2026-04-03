<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListClicksRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksHandler;

final class ClickIndexController extends Controller
{
    public function __invoke(
        ListClicksRequest $request,
        DateRangeQueryResolver $dateRangeResolver,
        ListClicksHandler $handler,
    ): View {
        $clicks = $handler($request->toQuery($dateRangeResolver));

        return view('admin.clicks.index', [
            'clicks' => $clicks,
            'drillContextItems' => $request->drillContextItems($dateRangeResolver),
            'paginationQuery' => $request->paginationQuery($dateRangeResolver),
        ]);
    }
}
