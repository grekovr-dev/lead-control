<?php

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Inbound\Domain\Lead\LeadStatus;

class LeadModel extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'name',
        'phone',
        'comment',
        'status',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
    ];
}
