<?php

declare(strict_types=1);

namespace Inbound\Application\Events;

interface EventBus
{
    public function publish(object ...$events): void;
}
