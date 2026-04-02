<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterTouch;

use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitCommand;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchRepository;

final class RegisterTouchAction
{
    public function __construct(
        private TouchRepository $touchRepository,
        private ContinueCurrentVisitAction $continueCurrentVisitAction,
        private TransactionManager $transactionManager,
    ) {
    }

    public function __invoke(RegisterTouchCommand $command): Touch
    {
        return $this->transactionManager->run(function () use ($command): Touch {
            $visit = ($this->continueCurrentVisitAction)(new ContinueCurrentVisitCommand(
                $command->visitorId,
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
        });
    }
}
