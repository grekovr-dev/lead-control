<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromForm;

use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Visit\VisitRepository;

final class CreateLeadFromFormAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private VisitRepository $visitRepository,
        private VisitSessionRule $visitSessionRule,
    ) {
    }

    public function __invoke(CreateLeadFromFormCommand $command): Lead
    {
        $visit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($visit === null || !$this->visitSessionRule->continues($visit, $command->occurredAt)) {
            throw new ActiveVisitNotFoundException('Cannot create lead from form without an active visit.');
        }

        $lead = new Lead(
            $command->leadId,
            $command->visitorId,
            $visit->id(),
            $command->name,
            $command->phone,
            $command->attribution,
            LeadStatus::NEW,
            'form',
            $command->occurredAt,
        );

        $this->leadRepository->save($lead);

        return $lead;
    }
}
