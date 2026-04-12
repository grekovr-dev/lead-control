<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromForm;

use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitCommand;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException as ContinueCurrentVisitNotFoundException;
use Inbound\Application\Events\EventBus;
use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Visit\VisitRepository;

final class CreateLeadFromFormAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private VisitRepository $visitRepository,
        private ContinueCurrentVisitAction $continueCurrentVisitAction,
        private UuidGenerator $uuidGenerator,
        private EventBus $eventBus,
        private TransactionManager $transactionManager,
    ) {}

    public function __invoke(CreateLeadFromFormCommand $command): Lead
    {
        $lead = $this->transactionManager->run(function () use ($command): Lead {
            try {
                $visit = ($this->continueCurrentVisitAction)(new ContinueCurrentVisitCommand(
                    $command->visitorId,
                    $command->occurredAt,
                ));
            } catch (ContinueCurrentVisitNotFoundException $exception) {
                throw new CurrentVisitNotFoundException('Cannot create lead from form without a current visit.');
            }

            $firstVisit = $this->visitRepository->findFirstByVisitorId($command->visitorId);

            if ($firstVisit === null) {
                throw new \RuntimeException('Cannot create lead from form without a first visit for the visitor.');
            }

            $lead = Lead::create(
                new LeadId($this->uuidGenerator->generate()),
                $command->visitorId,
                $visit->id(),
                $command->name,
                $command->phone,
                $visit->firstAttribution(),
                LeadStatus::NEW,
                'form',
                $command->occurredAt,
                $firstVisit->firstAttribution(),
                $visit->landingUrl(),
            );

            $this->leadRepository->save($lead);

            return $lead;
        });

        $this->eventBus->publish(...$lead->releaseEvents());

        return $lead;
    }
}
