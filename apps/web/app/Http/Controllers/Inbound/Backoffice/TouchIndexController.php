<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListTouchesRequest;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesHandler;

final class TouchIndexController extends Controller
{
    public function __invoke(ListTouchesRequest $request, ListTouchesHandler $handler): View
    {
        $touches = $handler($request->toQuery());

        return view('admin.touches.index', [
            'touches' => $touches,
            'filters' => $request->filters(),
            'perPageOptions' => $request->perPageOptions(),
            'typeOptions' => $request->typeOptions(),
            'paginationQuery' => $request->paginationQuery(),
        ]);
    }
}
