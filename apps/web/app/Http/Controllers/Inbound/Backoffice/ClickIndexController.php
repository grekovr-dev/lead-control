<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListClicksRequest;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksHandler;

final class ClickIndexController extends Controller
{
    public function __invoke(ListClicksRequest $request, ListClicksHandler $handler): View
    {
        $clicks = $handler($request->toQuery());

        return view('admin.clicks.index', [
            'clicks' => $clicks,
            'filters' => $request->filters(),
            'perPageOptions' => $request->perPageOptions(),
            'paginationQuery' => $request->paginationQuery(),
        ]);
    }
}
