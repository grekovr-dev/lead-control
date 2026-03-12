<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNoteModel::class, 'lead_id')->latest();
    }
}
