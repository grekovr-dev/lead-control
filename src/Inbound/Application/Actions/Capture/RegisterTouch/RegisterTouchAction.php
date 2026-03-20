<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterTouch;

use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitRepository;

final class RegisterTouchAction
{
    public function __construct(
        private TouchRepository $touchRepository,
        private VisitRepository $visitRepository,
    ) {
    }

    public function __invoke(RegisterTouchCommand $command): Touch
    {
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

        $touch = new Touch(
            $command->touchId,
            $visit->id(),
            $command->visitorId,
            $command->type,
            $command->occurredAt,
        );

        $this->touchRepository->save($touch);
        $this->visitRepository->save($visit);

        return $touch;
    }
}
