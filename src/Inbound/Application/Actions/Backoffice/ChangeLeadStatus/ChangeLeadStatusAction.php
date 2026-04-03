<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Backoffice\ChangeLeadStatus;

use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransition;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;

final class ChangeLeadStatusAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private LeadStatusTransitionRepository $leadStatusTransitionRepository,
    ) {
    }

    /**
     * @throws LeadNotFoundException
     */
    public function __invoke(ChangeLeadStatusCommand $command): Lead
    {
        $lead = $this->leadRepository->findById($command->leadId);

        if ($lead === null) {
            throw new LeadNotFoundException(sprintf(
                'Cannot change status for missing lead "%s".',
                $command->leadId->value(),
            ));
        }

        if ($lead->status() === $command->status) {
            return $lead;
        }

        $fromStatus = $lead->status();

        $lead->changeStatus($command->status);

        $this->leadRepository->save($lead);

        $transition = new LeadStatusTransition(
            $lead->id(),
            $fromStatus,
            $command->status,
            $command->ruleKey,
            $command->changedAt,
        );

        $this->leadStatusTransitionRepository->save($transition);

        return $lead;
    }
}
