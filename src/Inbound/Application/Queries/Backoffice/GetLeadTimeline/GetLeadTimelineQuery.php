<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadTimeline;

use Inbound\Domain\Lead\LeadId;

final readonly class GetLeadTimelineQuery
{
    public function __construct(
        public LeadId $leadId,
    ) {
    }
}
