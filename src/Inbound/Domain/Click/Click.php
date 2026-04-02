<?php

declare(strict_types=1);

namespace Inbound\Domain\Click;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;

final class Click
{
    private ClickId $id;

    private VisitorId $visitorId;

    private ?VisitId $visitId;

    private Attribution $attribution;

    private string $landingUrl;

    private DateTimeImmutable $occurredAt;

    public function __construct(
        ClickId $id,
        VisitorId $visitorId,
        Attribution $attribution,
        string $landingUrl,
        DateTimeImmutable $occurredAt,
        ?VisitId $visitId = null,
    ) {
        $landingUrl = trim($landingUrl);

        if ($landingUrl === '') {
            throw new InvalidArgumentException('Click landingUrl cannot be empty.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->visitId = $visitId;
        $this->attribution = $attribution;
        $this->landingUrl = $landingUrl;
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

    public function visitId(): ?VisitId
    {
        return $this->visitId;
    }

    public function attribution(): Attribution
    {
        return $this->attribution;
    }

    public function landingUrl(): string
    {
        return $this->landingUrl;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
