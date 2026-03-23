<?php

declare(strict_types=1);

namespace Inbound\Domain\Click;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use InvalidArgumentException;

final class Click
{
    private ClickId $id;
    private VisitorId $visitorId;
    private Attribution $attribution;
    private string $landingUrl;
    private ?string $referrer;
    private DateTimeImmutable $occurredAt;

    public function __construct(
        ClickId $id,
        VisitorId $visitorId,
        Attribution $attribution,
        string $landingUrl,
        ?string $referrer,
        DateTimeImmutable $occurredAt,
    ) {
        $landingUrl = trim($landingUrl);

        if ($landingUrl === '') {
            throw new InvalidArgumentException('Click landingUrl cannot be empty.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->attribution = $attribution;
        $this->landingUrl = $landingUrl;
        $this->referrer = self::normalizeNullableString($referrer);
        $this->occurredAt = $occurredAt;
    }

    public function id(): ClickId
    {
        return $this->id;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function attribution(): Attribution
    {
        return $this->attribution;
    }

    public function landingUrl(): string
    {
        return $this->landingUrl;
    }

    public function referrer(): ?string
    {
        return $this->referrer;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
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
