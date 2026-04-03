<?php

declare(strict_types=1);

namespace Inbound\Domain\LeadStatusHistory;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use InvalidArgumentException;

final class LeadStatusTransition
{
    private LeadId $leadId;
    private LeadStatus $fromStatus;
    private LeadStatus $toStatus;
    private string $ruleKey;
    private DateTimeImmutable $changedAt;

    public function __construct(
        LeadId $leadId,
        LeadStatus $fromStatus,
        LeadStatus $toStatus,
        string $ruleKey,
        DateTimeImmutable $changedAt,
    ) {
        $ruleKey = trim($ruleKey);

        if ($ruleKey === '') {
            throw new InvalidArgumentException('LeadStatusTransition ruleKey cannot be empty.');
        }

        if ($fromStatus === $toStatus) {
            throw new InvalidArgumentException('LeadStatusTransition fromStatus and toStatus must differ.');
        }

        $this->leadId = $leadId;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->ruleKey = $ruleKey;
        $this->changedAt = $changedAt;
    }

    public function leadId(): LeadId
    {
        return $this->leadId;
    }

    public function fromStatus(): LeadStatus
    {
        return $this->fromStatus;
    }

    public function toStatus(): LeadStatus
    {
        return $this->toStatus;
    }

    public function ruleKey(): string
    {
        return $this->ruleKey;
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }
}
