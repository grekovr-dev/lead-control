<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ResolveVisitForCapture;

use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitRepository;

final class ResolveVisitForCaptureAction
{
    public function __construct(
        private VisitRepository $visitRepository,
        private VisitSessionRule $visitSessionRule,
    ) {
    }

    public function __invoke(ResolveVisitForCaptureCommand $command): Visit
    {
        $lastVisit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($lastVisit !== null && $this->visitSessionRule->continues($lastVisit, $command->occurredAt)) {
            $lastVisit->touch($command->attribution, $command->occurredAt);
            $this->visitRepository->save($lastVisit);

            return $lastVisit;
        }

        $visit = new Visit(
            $command->visitId,
            $command->visitorId,
            $command->attribution,
            $command->attribution,
            $command->occurredAt,
            $command->occurredAt,
        );

        $this->visitRepository->save($visit);

        return $visit;
    }
}
