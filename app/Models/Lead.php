<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'category',
        'address',
        'phone',
        'email',
        'google_maps_url',
        'rating',
        'reviews_count',
        'website',
        'ai_score',
        'approved_for_outreach',
        'contacted',
        'email_status',
        'follow_up_due_at',
        'follow_up_sent',
        'sms_sent_at',
        'sms_follow_up_due_at',
        'sms_follow_up_sent',
        'last_review_year',
        'archived_at',
        'notes',
    ];

    protected $casts = [
        'rating'                => 'decimal:1',
        'ai_score'              => 'integer',
        'reviews_count'         => 'integer',
        'last_review_year'      => 'integer',
        'approved_for_outreach' => 'boolean',
        'contacted'             => 'boolean',
        'follow_up_due_at'      => 'datetime',
        'follow_up_sent'        => 'boolean',
        'sms_sent_at'           => 'datetime',
        'sms_follow_up_due_at'  => 'datetime',
        'sms_follow_up_sent'    => 'boolean',
        'archived_at'           => 'datetime',
        'notes'                 => 'string',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function outreachLogs(): HasMany
    {
        return $this->hasMany(OutreachLog::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeHighScore($query, int $threshold = 7)
    {
        return $query->where('ai_score', '>=', $threshold);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved_for_outreach', true);
    }

    public function scopeContacted($query)
    {
        return $query->where('contacted', true);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeReadyForOutreach($query)
    {
        return $query->whereNull('archived_at')
                     ->where('approved_for_outreach', true)
                     ->where('contacted', false)
                     ->where('email_status', 'pending');
    }

    /**
     * Leads whose follow-up reminder is due and not yet sent.
     */
    public function scopeFollowUpDue($query)
    {
        return $query->whereNull('archived_at')
                     ->where('email_status', 'sent')
                     ->where('follow_up_sent', false)
                     ->whereNotNull('follow_up_due_at')
                     ->where('follow_up_due_at', '<=', now());
    }

    /**
     * Approved leads not yet contacted via SMS.
     */
    public function scopeReadyForSmsOutreach($query)
    {
        return $query->whereNull('archived_at')
                     ->where('approved_for_outreach', true)
                     ->where('contacted', false)
                     ->whereNull('sms_sent_at');
    }

    /**
     * Leads whose SMS follow-up is due and not yet sent.
     */
    public function scopeSmsFollowUpDue($query)
    {
        return $query->whereNull('archived_at')
                     ->whereNotNull('sms_sent_at')
                     ->where('sms_follow_up_sent', false)
                     ->whereNotNull('sms_follow_up_due_at')
                     ->where('sms_follow_up_due_at', '<=', now());
    }
}
