<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick;

use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Visit\VisitRepository;

final class CreateLeadFromPhoneClickAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private VisitRepository $visitRepository,
    ) {
    }

    public function __invoke(CreateLeadFromPhoneClickCommand $command): Lead
    {
        $visit = $this->visitRepository->findActiveByVisitorId($command->visitorId);

        if ($visit === null) {
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
