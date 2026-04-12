<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Infrastructure\Events;

use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\LeadId;
use Inbound\Infrastructure\Events\LaravelEventBus;
use PHPUnit\Framework\TestCase;

final class LaravelEventBusTest extends TestCase
{
    public function test_it_dispatches_each_published_event_through_laravel_dispatcher(): void
    {
        $firstEvent = new LeadCreated(
            new LeadId('lead-123'),
            new DateTimeImmutable('2026-04-08T12:00:00+03:00'),
        );
        $secondEvent = new LeadCreated(
            new LeadId('lead-456'),
            new DateTimeImmutable('2026-04-08T12:05:00+03:00'),
        );

        $dispatcher = $this->createMock(Dispatcher::class);

        $dispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->callback(static fn (object $event): bool => in_array($event, [$firstEvent, $secondEvent], true)));

        $eventBus = new LaravelEventBus($dispatcher);

        $eventBus->publish($firstEvent, $secondEvent);
    }
}
