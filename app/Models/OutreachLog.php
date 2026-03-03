<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'channel',
        'email',
        'status',
        'response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
