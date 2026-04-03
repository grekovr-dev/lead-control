<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsQuery;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsNotFoundException;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineHandler;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineQuery;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineNotFoundException;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class LeadShowController extends Controller
{
    public function __invoke(
        string $leadId,
        GetLeadDetailsHandler $detailsHandler,
        GetLeadTimelineHandler $timelineHandler,
    ): View {
        try {
            $leadId = new LeadId($leadId);
            $details = $detailsHandler(new GetLeadDetailsQuery($leadId));
            $timeline = $timelineHandler(new GetLeadTimelineQuery($leadId));
        } catch (LeadDetailsNotFoundException|LeadTimelineNotFoundException|InvalidArgumentException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return view('admin.leads.show', [
            'details' => $details,
            'timeline' => $timeline,
            'statusOptions' => LeadStatus::options(),
        ]);
    }
}
