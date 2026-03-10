<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\StoreLeadRequest;
use Inbound\Application\Actions\CreateLeadAction;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, CreateLeadAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()
            ->back()
            ->withFragment('lead-form')
            ->with('success', 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.');
    }
}
