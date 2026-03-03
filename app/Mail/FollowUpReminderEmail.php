<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $leads,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->leads->count();
        $label = $count === 1 ? '1 lead' : "{$count} leads";

        return new Envelope(
            subject: "Follow-Up Reminder: {$label} awaiting your response",
        );
    }

    public function content(): Content
    {
        $followUpDays = (int) Setting::get('follow_up_days', config('leads.follow_up_days', 4));

        return new Content(
            view: 'emails.followup_reminder',
            with: [
                'leads'        => $this->leads,
                'followUpDays' => $followUpDays,
                'appUrl'       => config('app.url'),
                'appName'      => config('app.name'),
            ],
        );
    }
}
