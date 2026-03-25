<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CapturePhoneClick;

use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
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
        private VisitSessionRule $visitSessionRule,
    ) {
    }

    public function __invoke(CapturePhoneClickCommand $command): Lead|Touch
    {
        $visit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($visit === null || !$this->visitSessionRule->continues($visit, $command->occurredAt)) {
            throw new ActiveVisitNotFoundException('Cannot capture phone click without an active visit.');
        }

        $existingPhoneClickLead = $this->leadRepository->findByVisitIdAndOrigin($visit->id(), 'phone_click');

        if ($existingPhoneClickLead === null) {
            $lead = new Lead(
                $command->leadId,
                $command->visitorId,
                $visit->id(),
                null,
                null,
                $command->attribution,
                LeadStatus::NEW,
                'phone_click',
                $command->occurredAt,
            );

            $this->leadRepository->save($lead);

            return $lead;
        }

        $visit->touch($command->attribution, $command->occurredAt);
        $this->visitRepository->save($visit);

        $touch = new Touch(
            $command->touchId,
            $visit->id(),
            $command->visitorId,
            TouchType::PhoneClick,
            $command->occurredAt,
        );

        $this->touchRepository->save($touch);

        return $touch;
    }
}
