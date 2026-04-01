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
    private ?string $landingUrl;
    private DateTimeImmutable $startedAt;
    private DateTimeImmutable $lastTouchedAt;

    public function __construct(
        VisitId $id,
        VisitorId $visitorId,
        Attribution $firstAttribution,
        Attribution $lastAttribution,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $lastTouchedAt,
        ?string $landingUrl = null,
    ) {
        if ($startedAt > $lastTouchedAt) {
            throw new InvalidArgumentException('Visit startedAt cannot be later than lastTouchedAt.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->firstAttribution = $firstAttribution;
        $this->lastAttribution = $lastAttribution;
        $this->landingUrl = self::normalizeNullableString($landingUrl);
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

    public function landingUrl(): ?string
    {
        return $this->landingUrl;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function lastTouchedAt(): DateTimeImmutable
    {
        return $this->lastTouchedAt;
    }

    public function touch(DateTimeImmutable $occurredAt): void
    {
        $this->assertTouchOccurredAt($occurredAt);
        $this->lastTouchedAt = $occurredAt;
    }

    public function touchWithAttribution(Attribution $attribution, DateTimeImmutable $occurredAt): void
    {
        $this->touch($occurredAt);
        $this->lastAttribution = $attribution;
    }

    private function assertTouchOccurredAt(DateTimeImmutable $occurredAt): void
    {
        if ($occurredAt < $this->startedAt) {
            throw new InvalidArgumentException('Visit touch occurredAt cannot be earlier than startedAt.');
        }

        if ($occurredAt < $this->lastTouchedAt) {
            throw new InvalidArgumentException('Visit touch occurredAt cannot be earlier than lastTouchedAt.');
        }
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
