<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadTimeline;

interface LeadTimelineReadModel
{
    /**
     * @throws LeadTimelineNotFoundException
     */
    public function __invoke(GetLeadTimelineQuery $query): LeadTimelineView;
}
