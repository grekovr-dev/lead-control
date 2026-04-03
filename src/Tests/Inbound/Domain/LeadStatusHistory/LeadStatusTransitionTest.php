<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\LeadStatusHistory;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LeadStatusTransitionTest extends TestCase
{
    public function test_it_exposes_its_data_and_normalizes_rule_key(): void
    {
        $leadId = new LeadId('lead-123');
        $changedAt = new DateTimeImmutable('2026-03-28T12:00:00+02:00');

        $transition = new LeadStatusTransition(
            $leadId,
            LeadStatus::NEW,
            LeadStatus::CONTACTED,
            ' manual_backoffice ',
            $changedAt,
        );

        $this->assertSame($leadId, $transition->leadId());
        $this->assertSame(LeadStatus::NEW, $transition->fromStatus());
        $this->assertSame(LeadStatus::CONTACTED, $transition->toStatus());
        $this->assertSame('manual_backoffice', $transition->ruleKey());
        $this->assertSame($changedAt, $transition->changedAt());
    }

    public function test_it_rejects_an_empty_rule_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadStatusTransition(
            new LeadId('lead-123'),
            LeadStatus::NEW,
            LeadStatus::CONTACTED,
            '',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }

    public function test_it_rejects_a_whitespace_only_rule_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadStatusTransition(
            new LeadId('lead-123'),
            LeadStatus::NEW,
            LeadStatus::CONTACTED,
            '   ',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }

    public function test_it_rejects_transition_to_the_same_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadStatusTransition(
            new LeadId('lead-123'),
            LeadStatus::QUALIFIED,
            LeadStatus::QUALIFIED,
            'qualified_after_call',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }
}
