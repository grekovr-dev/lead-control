<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inbound\Domain\Lead\LeadStatus;

class LeadStatusTransitionModel extends Model
{
    public $timestamps = false;

    protected $table = 'lead_status_transitions';

    protected $fillable = [
        'lead_id',
        'from_status',
        'to_status',
        'rule_key',
        'changed_at',
    ];

    protected $casts = [
        'from_status' => LeadStatus::class,
        'to_status' => LeadStatus::class,
        'changed_at' => 'immutable_datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadModel::class, 'lead_id');
    }
}
