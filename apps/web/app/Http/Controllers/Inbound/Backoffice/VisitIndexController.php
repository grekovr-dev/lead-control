<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListVisitsRequest;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsHandler;

final class VisitIndexController extends Controller
{
    public function __invoke(ListVisitsRequest $request, ListVisitsHandler $handler): View
    {
        $visits = $handler($request->toQuery());

        return view('admin.visits.index', [
            'visits' => $visits,
            'filters' => $request->filters(),
            'perPageOptions' => $request->perPageOptions(),
            'paginationQuery' => $request->paginationQuery(),
        ]);
    }
}
