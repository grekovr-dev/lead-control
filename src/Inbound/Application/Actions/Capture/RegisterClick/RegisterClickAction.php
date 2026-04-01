<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitCommand;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Visit\Visit;

final class RegisterClickAction
{
    public function __construct(
        private ClickRepository $clickRepository,
        private ResolveCurrentVisitAction $resolveCurrentVisitAction,
        private TransactionManager $transactionManager,
    ) {}

    public function __invoke(RegisterClickCommand $command): Visit
    {
        return $this->transactionManager->run(function () use ($command): Visit {
            $attribution = $command->attribution;

            $visit = ($this->resolveCurrentVisitAction)(new ResolveCurrentVisitCommand(
                $command->visitId,
                $command->visitorId,
                $attribution,
                $command->occurredAt,
                $command->landingUrl,
            ));

            $click = new Click(
                $command->clickId,
                $command->visitorId,
                $attribution,
                $command->landingUrl,
                $command->occurredAt,
                $visit->id(),
            );

            $this->clickRepository->save($click);

            return $visit;
        });
    }
}
