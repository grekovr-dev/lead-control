<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureCommand;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Visit\Visit;

final class RegisterClickAction
{
    public function __construct(
        private ClickRepository $clickRepository,
        private ResolveVisitForCaptureAction $resolveVisitForCaptureAction,
    ) {
    }

    public function __invoke(RegisterClickCommand $command): Visit
    {
        $visit = ($this->resolveVisitForCaptureAction)(new ResolveVisitForCaptureCommand(
            $command->visitId,
            $command->visitorId,
            $command->attribution,
            $command->occurredAt,
        ));

        $click = new Click(
            $command->clickId,
            $command->visitorId,
            $command->attribution,
            $command->landingUrl,
            null,
            $command->occurredAt,
        );

        $this->clickRepository->save($click);

        return $visit;
    }
}
