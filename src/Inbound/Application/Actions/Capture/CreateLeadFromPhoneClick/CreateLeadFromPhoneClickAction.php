<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick;

use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Visit\VisitRepository;

final class CreateLeadFromPhoneClickAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private VisitRepository $visitRepository,
        private VisitSessionRule $visitSessionRule,
    ) {
    }

    public function __invoke(CreateLeadFromPhoneClickCommand $command): Lead
    {
        $visit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($visit === null || !$this->visitSessionRule->continues($visit, $command->occurredAt)) {
            throw new ActiveVisitNotFoundException('Cannot create lead from phone click without an active visit.');
        }

        $lead = new Lead(
            $command->leadId,
            $command->visitorId,
            $visit->id(),
            null,
            $command->phone,
            $command->attribution,
            LeadStatus::NEW,
            'phone_click',
            $command->occurredAt,
        );

        $this->leadRepository->save($lead);

        return $lead;
    }
}
