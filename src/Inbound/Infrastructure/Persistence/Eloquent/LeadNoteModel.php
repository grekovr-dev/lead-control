<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadNoteModel extends Model
{
    protected $table = 'lead_notes';

    protected $fillable = [
        'lead_id',
        'author_id',
        'note',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadModel::class, 'lead_id');
    }
}
