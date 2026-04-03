<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Backoffice\UpdateLeadStatusRequest;
use Illuminate\Http\RedirectResponse;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusAction;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\LeadNotFoundException;
use InvalidArgumentException;

final class UpdateLeadStatusController extends Controller
{
    public function __invoke(
        UpdateLeadStatusRequest $request,
        ChangeLeadStatusAction $action,
        string $leadId,
    ): RedirectResponse
    {
        try {
            $action($request->toCommand($leadId));
        } catch (LeadNotFoundException|InvalidArgumentException) {
            return redirect()
                ->route('admin.leads.index')
                ->with('error', 'Не вдалося змінити статус: лід не знайдено.');
        }

        return redirect()
            ->route('admin.leads.show', ['leadId' => $leadId], 303)
            ->withFragment('lead-status-form')
            ->with('success', 'Статус збережено.')
            ->with('success_context', 'lead_status');
    }
}
