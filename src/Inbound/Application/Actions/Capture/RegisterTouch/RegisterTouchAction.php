<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterTouch;

use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureCommand;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchRepository;

final class RegisterTouchAction
{
    public function __construct(
        private TouchRepository $touchRepository,
        private ResolveVisitForCaptureAction $resolveVisitForCaptureAction,
    ) {
    }

    public function __invoke(RegisterTouchCommand $command): Touch
    {
        $visit = ($this->resolveVisitForCaptureAction)(new ResolveVisitForCaptureCommand(
            $command->visitId,
            $command->visitorId,
            $command->attribution,
            $command->occurredAt,
        ));

        $touch = new Touch(
            $command->touchId,
            $visit->id(),
            $command->visitorId,
            $command->type,
            $command->occurredAt,
        );

        $this->touchRepository->save($touch);

        return $touch;
    }
}
