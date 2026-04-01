<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\ContinueCurrentVisit;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitCommand;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class ContinueCurrentVisitActionTest extends TestCase
{
    public function test_it_continues_last_visit_when_it_exists(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T11:10:00+02:00');
        $command = new ContinueCurrentVisitCommand(
            new VisitorId('visitor-456'),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T11:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T11:05:00+02:00'),
        );

        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($existingVisit, $occurredAt): bool {
                return $visit === $existingVisit
                    && $visit->lastAttribution()->medium() === 'remarketing'
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new ContinueCurrentVisitAction($visitRepository);

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_throws_when_last_visit_is_missing(): void
    {
        $command = new ContinueCurrentVisitCommand(
            new VisitorId('visitor-456'),
            new DateTimeImmutable('2026-03-20T11:10:00+02:00'),
        );

        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->never())
            ->method('save');

        $action = new ContinueCurrentVisitAction($visitRepository);

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot continue current visit without an existing visit.');

        $action($command);
    }
}
