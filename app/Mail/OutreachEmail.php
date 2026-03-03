<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutreachEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We noticed {$this->lead->business_name} doesn't have a website",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.outreach',
            with: [
                'lead'            => $this->lead,
                'senderName'      => Setting::get('sender_name',      'The Web Team'),
                'senderPosition'  => Setting::get('sender_position',  ''),
                'companyName'     => Setting::get('company_name',     ''),
                'companyEmail'    => Setting::get('company_email',    ''),
                'companyPhone'    => Setting::get('company_phone',    ''),
                'companyWhatsApp' => Setting::get('company_whatsapp', ''),
            ],
        );
    }
}
