<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitRepository;

final class RegisterClickAction
{
    public function __construct(
        private ClickRepository $clickRepository,
        private VisitRepository $visitRepository,
    ) {
    }

    public function __invoke(RegisterClickCommand $command): Visit
    {
        $click = new Click(
            $command->clickId,
            $command->visitorId,
            $command->attribution,
            $command->landingUrl,
            null,
            $command->occurredAt,
        );

        $visit = $this->visitRepository->findActiveByVisitorId($command->visitorId);

        if ($visit === null) {
            $visit = new Visit(
                $command->visitId,
                $command->visitorId,
                $command->attribution,
                $command->attribution,
                $command->occurredAt,
                $command->occurredAt,
            );
        } else {
            $visit->touch($command->attribution, $command->occurredAt);
        }

        $this->clickRepository->save($click);
        $this->visitRepository->save($visit);

        return $visit;
    }
}
