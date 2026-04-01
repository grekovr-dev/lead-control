<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inbound\Domain\Lead\LeadStatus;

class LeadModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'leads';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visitor_id',
        'visit_id',
        'name',
        'phone',
        'status',
        'origin',
        'landing_url',
        'created_at',
        'visit_attribution_source',
        'visit_attribution_medium',
        'visit_attribution_campaign',
        'visit_attribution_content',
        'visit_attribution_term',
        'visit_attribution_gclid',
        'visit_attribution_fbclid',
        'visit_attribution_msclkid',
        'visit_attribution_referrer',
        'visitor_attribution_source',
        'visitor_attribution_medium',
        'visitor_attribution_campaign',
        'visitor_attribution_content',
        'visitor_attribution_term',
        'visitor_attribution_gclid',
        'visitor_attribution_fbclid',
        'visitor_attribution_msclkid',
        'visitor_attribution_referrer',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'created_at' => 'immutable_datetime',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNoteModel::class, 'lead_id')->latest();
    }
}
