<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lead\StoreLeadNoteRequest;
use Illuminate\Support\Facades\Auth;
use Inbound\Application\Actions\AddLeadNoteAction;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

class LeadNoteController extends Controller
{
    public function store(
        StoreLeadNoteRequest $request,
        LeadModel $lead,
        AddLeadNoteAction $action,
    ) {
        $action->execute(
            $lead,
            $request->validated('note'),
            Auth::id(),
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Заметка добавлена');
    }
}
