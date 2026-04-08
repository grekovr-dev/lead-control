<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Inbound\Application\Events\EventBus;

final readonly class LaravelEventBus implements EventBus
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {}

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
}
