<?php

declare(strict_types=1);

namespace Inbound\Domain\Visit;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use InvalidArgumentException;

final class Visit
{
    private VisitId $id;
    private VisitorId $visitorId;
    private Attribution $firstAttribution;
    private Attribution $lastAttribution;
    private DateTimeImmutable $startedAt;
    private DateTimeImmutable $lastTouchedAt;

    public function __construct(
        VisitId $id,
        VisitorId $visitorId,
        Attribution $firstAttribution,
        Attribution $lastAttribution,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $lastTouchedAt,
    ) {
        if ($startedAt > $lastTouchedAt) {
            throw new InvalidArgumentException('Visit startedAt cannot be later than lastTouchedAt.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->firstAttribution = $firstAttribution;
        $this->lastAttribution = $lastAttribution;
        $this->startedAt = $startedAt;
        $this->lastTouchedAt = $lastTouchedAt;
    }

    public function id(): VisitId
    {
        return $this->id;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function firstAttribution(): Attribution
    {
        return $this->firstAttribution;
    }

    public function lastAttribution(): Attribution
    {
        return $this->lastAttribution;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function lastTouchedAt(): DateTimeImmutable
    {
        return $this->lastTouchedAt;
    }

    public function touch(Attribution $attribution, DateTimeImmutable $occurredAt): void
    {
        if ($occurredAt < $this->startedAt) {
            throw new InvalidArgumentException('Visit touch occurredAt cannot be earlier than startedAt.');
        }

        if ($occurredAt < $this->lastTouchedAt) {
            throw new InvalidArgumentException('Visit touch occurredAt cannot be earlier than lastTouchedAt.');
        }

        $this->lastAttribution = $attribution;
        $this->lastTouchedAt = $occurredAt;
    }
}
