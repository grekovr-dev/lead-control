<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class VisitModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'visits';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visitor_id',
        'started_at',
        'last_touched_at',
        'first_attribution_source',
        'first_attribution_medium',
        'first_attribution_campaign',
        'first_attribution_content',
        'first_attribution_term',
        'first_attribution_gclid',
        'first_attribution_fbclid',
        'first_attribution_msclkid',
        'last_attribution_source',
        'last_attribution_medium',
        'last_attribution_campaign',
        'last_attribution_content',
        'last_attribution_term',
        'last_attribution_gclid',
        'last_attribution_fbclid',
        'last_attribution_msclkid',
    ];

    protected $casts = [
        'started_at' => 'immutable_datetime',
        'last_touched_at' => 'immutable_datetime',
    ];
}
