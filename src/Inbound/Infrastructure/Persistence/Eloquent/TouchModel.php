<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Inbound\Domain\Touch\TouchType;

class TouchModel extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'touches';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'visit_id',
        'visitor_id',
        'type',
        'occurred_at',
    ];

    protected $casts = [
        'type' => TouchType::class,
        'occurred_at' => 'immutable_datetime',
    ];
}
