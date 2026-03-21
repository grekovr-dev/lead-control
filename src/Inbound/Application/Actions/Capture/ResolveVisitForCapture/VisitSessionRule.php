<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ResolveVisitForCapture;

use DateInterval;
use DateTimeImmutable;
use Inbound\Domain\Visit\Visit;

final class VisitSessionRule
{
    private DateInterval $sessionLifetime;

    public function __construct(?DateInterval $sessionLifetime = null)
    {
        $this->sessionLifetime = $sessionLifetime ?? new DateInterval('PT30M');
    }

    public function continues(Visit $visit, DateTimeImmutable $occurredAt): bool
    {
        return $occurredAt <= $visit->lastTouchedAt()->add($this->sessionLifetime);
    }
}
