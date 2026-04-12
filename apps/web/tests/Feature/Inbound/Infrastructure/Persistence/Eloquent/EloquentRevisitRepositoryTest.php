<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Domain\Revisit\Revisit;
use Inbound\Domain\Revisit\RevisitId;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentRevisitRepository;
use Tests\TestCase;

final class EloquentRevisitRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_revisit(): void
    {
        $repository = new EloquentRevisitRepository;
        $revisit = new Revisit(
            new RevisitId('revisit-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'https://example.com/landing',
            new DateTimeImmutable('2026-04-12 10:00:00'),
        );

        $repository->save($revisit);

        $this->assertDatabaseCount('revisits', 1);
        $this->assertDatabaseHas('revisits', [
            'id' => 'revisit-123',
            'visitor_id' => 'visitor-456',
            'visit_id' => 'visit-789',
            'landing_url' => 'https://example.com/landing',
            'occurred_at' => '2026-04-12 10:00:00',
        ]);
    }

    public function test_it_returns_a_revisit_by_id(): void
    {
        $repository = new EloquentRevisitRepository;
        $revisit = new Revisit(
            new RevisitId('revisit-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'https://example.com/landing',
            new DateTimeImmutable('2026-04-12 10:00:00'),
        );

        $repository->save($revisit);

        $found = $repository->findById(new RevisitId('revisit-123'));

        $this->assertNotNull($found);
        $this->assertSame('revisit-123', $found->id()->value());
        $this->assertSame('visitor-456', $found->visitorId()->value());
        $this->assertSame('visit-789', $found->visitId()->value());
        $this->assertSame('https://example.com/landing', $found->landingUrl());
        $this->assertSame('2026-04-12 10:00:00', $found->occurredAt()->format('Y-m-d H:i:s'));
    }
}
