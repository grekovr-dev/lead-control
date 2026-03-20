<?php

declare(strict_types=1);

namespace Inbound\Domain\Touch;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final class Touch
{
    private TouchId $id;
    private VisitId $visitId;
    private VisitorId $visitorId;
    private TouchType $type;
    private DateTimeImmutable $occurredAt;

    public function __construct(
        TouchId $id,
        VisitId $visitId,
        VisitorId $visitorId,
        TouchType $type,
        DateTimeImmutable $occurredAt,
    ) {
        $this->id = $id;
        $this->visitId = $visitId;
        $this->visitorId = $visitorId;
        $this->type = $type;
        $this->occurredAt = $occurredAt;
    }

    public function id(): TouchId
    {
        return $this->id;
    }

    public function visitId(): VisitId
    {
        return $this->visitId;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function type(): TouchType
    {
        return $this->type;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
