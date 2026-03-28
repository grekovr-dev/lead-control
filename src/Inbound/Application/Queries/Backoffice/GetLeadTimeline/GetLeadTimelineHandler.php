<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadTimeline;

final readonly class GetLeadTimelineHandler
{
    public function __construct(
        private LeadTimelineReadModel $readModel,
    ) {
    }

    /**
     * @throws LeadTimelineNotFoundException
     */
    public function __invoke(GetLeadTimelineQuery $query): LeadTimelineView
    {
        return ($this->readModel)($query);
    }
}
