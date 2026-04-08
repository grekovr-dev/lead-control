<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Events;

use Inbound\Application\Events\EventBus;

final class NullEventBus implements EventBus
{
    public function publish(object ...$events): void {}
}
