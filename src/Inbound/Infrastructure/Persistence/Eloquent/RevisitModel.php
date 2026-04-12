<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class RevisitModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'revisits';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visitor_id',
        'visit_id',
        'landing_url',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'immutable_datetime',
    ];
}
