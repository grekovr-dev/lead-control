<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\PhoneClick;

use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitCommand;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException as ContinueCurrentVisitNotFoundException;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitRepository;

final class CapturePhoneClickAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private TouchRepository $touchRepository,
        private VisitRepository $visitRepository,
        private ContinueCurrentVisitAction $continueCurrentVisitAction,
        private TransactionManager $transactionManager,
    ) {
    }

    public function __invoke(CapturePhoneClickCommand $command): Lead|Touch
    {
        return $this->transactionManager->run(function () use ($command): Lead|Touch {
            try {
                $visit = ($this->continueCurrentVisitAction)(new ContinueCurrentVisitCommand(
                    $command->visitorId,
                    $command->occurredAt,
                ));
            } catch (ContinueCurrentVisitNotFoundException $exception) {
                throw new CurrentVisitNotFoundException('Cannot capture phone click without a current visit.');
            }

            $existingPhoneClickLead = $this->leadRepository->findByVisitIdAndOrigin($visit->id(), 'phone_click');

            if ($existingPhoneClickLead === null) {
                $firstVisit = $this->visitRepository->findFirstByVisitorId($command->visitorId);

                if ($firstVisit === null) {
                    throw new \RuntimeException('Cannot capture phone click without a first visit for the visitor.');
                }

                $lead = new Lead(
                    $command->leadId,
                    $command->visitorId,
                    $visit->id(),
                    null,
                    null,
                    $visit->firstAttribution(),
                    LeadStatus::NEW,
                    'phone_click',
                    $command->occurredAt,
                    $firstVisit->firstAttribution(),
                    $visit->landingUrl(),
                );

                $this->leadRepository->save($lead);

                return $lead;
            }

            $touch = new Touch(
                $command->touchId,
                $visit->id(),
                $command->visitorId,
                TouchType::PhoneClick,
                $command->occurredAt,
            );

            $this->touchRepository->save($touch);

            return $touch;
        });
    }
}
