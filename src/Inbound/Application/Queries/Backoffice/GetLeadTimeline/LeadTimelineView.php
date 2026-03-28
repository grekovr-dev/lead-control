<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadTimeline;

/**
 * @phpstan-type LeadTimelineEventList list<LeadTimelineEventView>
 */
final readonly class LeadTimelineView
{
    /**
     * @param LeadTimelineEventList $events
     */
    public function __construct(
        public string $leadId,
        public array $events,
    ) {
    }
}
