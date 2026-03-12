<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lead\UpdateLeadStatusRequest;
use Inbound\Application\Actions\UpdateLeadStatusAction;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

class LeadController extends Controller
{
    public function index()
    {
        $leads = LeadModel::query()
            ->latest()
            ->paginate(20);

        return view('admin.leads.index', [
            'leads' => $leads,
        ]);
    }

    public function show(LeadModel $lead)
    {
        $lead->load('notes');

        return view('admin.leads.show', [
            'lead' => $lead,
            'statuses' => LeadStatus::cases(),
        ]);
    }

    public function updateStatus(
        UpdateLeadStatusRequest $request,
        LeadModel $lead,
        UpdateLeadStatusAction $action,
    ) {
        $action->execute(
            $lead,
            LeadStatus::from($request->validated('status')),
        );

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Статус обновлён');
    }
}
