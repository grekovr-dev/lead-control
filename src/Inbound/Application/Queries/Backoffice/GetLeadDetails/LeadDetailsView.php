<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

final readonly class LeadDetailsView
{
    public function __construct(
        public LeadCoreView $lead,
        public ?LeadVisitSummaryView $visit,
        public ActivitySummaryView $preLeadTouchSummary,
        public ActivitySummaryView $preLeadVisitorClickSummary,
    ) {
    }
}
