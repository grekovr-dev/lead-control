<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ContinueCurrentVisit;

use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitRepository;

final class ContinueCurrentVisitAction
{
    public function __construct(
        private VisitRepository $visitRepository,
    ) {
    }

    public function __invoke(ContinueCurrentVisitCommand $command): Visit
    {
        $visit = $this->visitRepository->findLastByVisitorId($command->visitorId);

        if ($visit === null) {
            throw new CurrentVisitNotFoundException('Cannot continue current visit without an existing visit.');
        }

        $visit->touch($command->occurredAt);
        $this->visitRepository->save($visit);

        return $visit;
    }
}
