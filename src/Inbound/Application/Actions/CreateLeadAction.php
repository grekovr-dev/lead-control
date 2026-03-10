<?php

namespace Inbound\Application\Actions;

use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

class CreateLeadAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): LeadModel
    {
        $status = $data['status'] ?? LeadStatus::NEW;

        if (is_string($status)) {
            $status = LeadStatus::from($status);
        }

        return LeadModel::create([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'],
            'comment' => $data['comment'] ?? null,
            'status' => $status,
        ]);
    }
}
