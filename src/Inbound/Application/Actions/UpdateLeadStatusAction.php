<?php

declare(strict_types=1);

namespace Inbound\Application\Actions;

use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

class UpdateLeadStatusAction
{
    public function execute(LeadModel $lead, LeadStatus $status): LeadModel
    {
        $lead->status = $status;
        $lead->save();

        return $lead->refresh();
    }
}
