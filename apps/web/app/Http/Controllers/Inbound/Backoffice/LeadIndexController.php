<?php

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\ListLeadsRequest;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsHandler;

class LeadIndexController extends Controller
{
    public function __invoke(ListLeadsRequest $request, ListLeadsHandler $handler): View
    {
        $leads = $handler($request->toQuery());

        return view('admin.leads.index', [
            'leads' => $leads,
            'filters' => $request->filters(),
            'statusOptions' => $request->statusOptions(),
            'originOptions' => $request->originOptions(),
            'perPageOptions' => $request->perPageOptions(),
            'paginationQuery' => $request->paginationQuery(),
        ]);
    }
}
