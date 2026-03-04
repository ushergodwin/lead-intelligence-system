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
        $subjectTemplate = Setting::get(
            'email_subject_template',
            "We noticed {business_name} doesn't have a website"
        );

        $subject = str_replace('{business_name}', $this->lead->business_name, $subjectTemplate);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.outreach',
            with: [
                'lead'            => $this->lead,
                'emailBody'       => $this->buildEmailBody(),
                'senderName'      => Setting::get('sender_name',      'The Web Team'),
                'senderPosition'  => Setting::get('sender_position',  ''),
                'companyName'     => Setting::get('company_name',     ''),
                'companyEmail'    => Setting::get('company_email',    ''),
                'companyPhone'    => Setting::get('company_phone',    ''),
                'companyWhatsApp' => Setting::get('company_whatsapp', ''),
            ],
        );
    }

    private function buildEmailBody(): string
    {
        $template = Setting::get('email_body_template', $this->defaultBodyTemplate());

        $processed = str_replace(
            ['{business_name}', '{reviews_count}', '{rating}'],
            [
                e($this->lead->business_name),
                number_format($this->lead->reviews_count ?? 0),
                $this->lead->rating ?? 'N/A',
            ],
            $template
        );

        // Convert plain-text line breaks to HTML paragraphs
        $paragraphs = array_filter(array_map('trim', explode("\n", $processed)));
        $html = implode('', array_map(fn ($p) => "<p>{$p}</p>", $paragraphs));

        return $html;
    }

    private function defaultBodyTemplate(): string
    {
        return "Hello {business_name} Team,\n"
             . "I noticed your business has strong visibility on Google with {reviews_count} reviews and a {rating} star rating — that's impressive.\n"
             . "However, I couldn't find a website for your business.\n"
             . "Did you know? Businesses with a website receive up to 70% more inquiries than those without one.\n"
             . "Many customers search online before calling or visiting. A simple, mobile-friendly website can help you appear more professional, receive more direct inquiries, rank better in Google search results, and increase trust from new customers.\n"
             . "We specialise in helping businesses establish a strong online presence at affordable cost.\n"
             . "Would you be open to a quick conversation this week?";
    }
}
