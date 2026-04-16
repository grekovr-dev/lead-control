<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitCommand;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitCommand;
use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Revisit\Revisit;
use Inbound\Domain\Revisit\RevisitId;
use Inbound\Domain\Revisit\RevisitRepository;
use Inbound\Domain\Shared\Attribution;

final class RegisterClickAction
{
    public function __construct(
        private ClickRepository $clickRepository,
        private RevisitRepository $revisitRepository,
        private ContinueCurrentVisitAction $continueCurrentVisitAction,
        private ResolveCurrentVisitAction $resolveCurrentVisitAction,
        private UuidGenerator $uuidGenerator,
        private TransactionManager $transactionManager,
    ) {}

    public function __invoke(RegisterClickCommand $command): RegisterClickResult
    {
        return $this->transactionManager->run(function () use ($command): RegisterClickResult {
            $attribution = $command->attribution;

            if ($attribution->equals(Attribution::direct())) {
                try {
                    $visit = ($this->continueCurrentVisitAction)(new ContinueCurrentVisitCommand(
                        $command->visitorId,
                        $command->occurredAt,
                    ));

                    $revisit = new Revisit(
                        new RevisitId($this->uuidGenerator->generate()),
                        $command->visitorId,
                        $visit->id(),
                        $command->landingUrl,
                        $command->occurredAt,
                    );

                    $this->revisitRepository->save($revisit);

                    return new RegisterClickResult(
                        $command->visitorId->value(),
                        $visit->id()->value(),
                        RegisterClickResult::TYPE_REVISIT,
                        $revisit->id()->value(),
                    );
                } catch (CurrentVisitNotFoundException) {
                    // Fall through to the normal click registration flow.
                }
            }

            $visit = ($this->resolveCurrentVisitAction)(new ResolveCurrentVisitCommand(
                $command->visitorId,
                $attribution,
                $command->occurredAt,
                $command->landingUrl,
            ));

            $click = new Click(
                new ClickId($this->uuidGenerator->generate()),
                $command->visitorId,
                $attribution,
                $command->landingUrl,
                $command->occurredAt,
                $visit->id(),
            );

            $this->clickRepository->save($click);

            return new RegisterClickResult(
                $command->visitorId->value(),
                $visit->id()->value(),
                RegisterClickResult::TYPE_CLICK,
                $click->id()->value(),
            );
        });
    }
}
