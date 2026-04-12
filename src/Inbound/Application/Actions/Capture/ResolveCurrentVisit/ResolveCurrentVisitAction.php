<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ResolveCurrentVisit;

use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;

final class ResolveCurrentVisitAction
{
    public function __construct(
        private VisitRepository $visitRepository,
        private VisitSessionRule $visitSessionRule,
        private UuidGenerator $uuidGenerator,
    ) {}

    public function __invoke(ResolveCurrentVisitCommand $command): Visit
    {
        $lastVisit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($lastVisit !== null && $this->visitSessionRule->continues($lastVisit, $command->occurredAt)) {
            $lastVisit->touchWithAttribution($command->attribution, $command->occurredAt);
            $this->visitRepository->save($lastVisit);

            return $lastVisit;
        }

        $visit = new Visit(
            new VisitId($this->uuidGenerator->generate()),
            $command->visitorId,
            $command->attribution,
            $command->attribution,
            $command->occurredAt,
            $command->occurredAt,
            $command->landingUrl,
        );

        $this->visitRepository->save($visit);

        return $visit;
    }
}
