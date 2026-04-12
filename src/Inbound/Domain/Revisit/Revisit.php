<?php

declare(strict_types=1);

namespace Inbound\Domain\Revisit;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;

final class Revisit
{
    private RevisitId $id;

    private VisitorId $visitorId;

    private VisitId $visitId;

    private string $landingUrl;

    private DateTimeImmutable $occurredAt;

    public function __construct(
        RevisitId $id,
        VisitorId $visitorId,
        VisitId $visitId,
        string $landingUrl,
        DateTimeImmutable $occurredAt,
    ) {
        $landingUrl = trim($landingUrl);

        if ($landingUrl === '') {
            throw new InvalidArgumentException('Revisit landingUrl cannot be empty.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->visitId = $visitId;
        $this->landingUrl = $landingUrl;
        $this->occurredAt = $occurredAt;
    }

    public function id(): RevisitId
    {
        return $this->id;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function visitId(): VisitId
    {
        return $this->visitId;
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
