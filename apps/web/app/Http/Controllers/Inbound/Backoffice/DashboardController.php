<?php

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard.index', [
            'leadsCount' => LeadModel::query()->count(),
            'newLeadsCount' => LeadModel::query()->where('status', LeadStatus::NEW->value)->count(),
            'wonLeadsCount' => LeadModel::query()->where('status', LeadStatus::WON->value)->count(),
            'lostLeadsCount' => LeadModel::query()->where('status', LeadStatus::LOST->value)->count(),
        ]);
    }
}
