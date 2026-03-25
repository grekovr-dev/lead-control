<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Touch;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitId;
use PHPUnit\Framework\TestCase;

final class TouchTest extends TestCase
{
    public function test_it_exposes_its_data(): void
    {
        $id = new TouchId('touch-123');
        $visitId = new VisitId('visit-456');
        $visitorId = new VisitorId('visitor-789');
        $type = TouchType::LeadFormClick;
        $occurredAt = new DateTimeImmutable('2026-03-19T12:00:00+02:00');

        $touch = new Touch(
            $id,
            $visitId,
            $visitorId,
            $type,
            $occurredAt,
        );

        $this->assertSame($id, $touch->id());
        $this->assertSame($visitId, $touch->visitId());
        $this->assertSame($visitorId, $touch->visitorId());
        $this->assertSame($type, $touch->type());
        $this->assertSame($occurredAt, $touch->occurredAt());
    }
}
