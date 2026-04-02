<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ClickModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'clicks';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visitor_id',
        'visit_id',
        'landing_url',
        'attribution_referrer',
        'occurred_at',
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
        'occurred_at' => 'immutable_datetime',
    ];
}
