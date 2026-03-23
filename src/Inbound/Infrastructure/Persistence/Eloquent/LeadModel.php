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
        'created_at',
        'attribution_source',
        'attribution_medium',
        'attribution_campaign',
        'attribution_content',
        'attribution_term',
        'attribution_gclid',
        'attribution_fbclid',
        'attribution_msclkid',
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
