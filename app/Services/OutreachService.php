<?php

namespace App\Services;

use App\Mail\OutreachEmail;
use App\Models\Lead;
use App\Models\OutreachLog;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OutreachService
{
    /**
     * Count emails sent today.
     */
    public function sentTodayCount(): int
    {
        return OutreachLog::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
    }

    /**
     * Check whether the daily limit has been reached.
     */
    public function dailyLimitReached(): bool
    {
        $limit = (int) Setting::get('daily_email_limit', config('leads.daily_email_limit'));
        return $this->sentTodayCount() >= $limit;
    }

    /**
     * Send an outreach email to the given lead using the OutreachEmail Mailable.
     * Records result in outreach_logs and updates the lead record.
     */
    public function sendEmail(Lead $lead, string $toEmail): void
    {
        if ($this->dailyLimitReached()) {
            Log::info('OutreachService: daily limit reached, skipping.', ['lead_id' => $lead->id]);
            return;
        }

        try {
            Mail::to($toEmail)->send(new OutreachEmail($lead));

            $followUpDays = (int) Setting::get('follow_up_days', config('leads.follow_up_days', 4));

            $this->logOutreach($lead, $toEmail, 'sent', 'Email delivered successfully.');
            $lead->update([
                'contacted'        => true,
                'email_status'     => 'sent',
                'follow_up_due_at' => Carbon::now()->addDays($followUpDays),
                'follow_up_sent'   => false,
            ]);

            Log::info('OutreachService: email sent', ['lead_id' => $lead->id, 'to' => $toEmail]);

        } catch (\Throwable $e) {
            $this->logOutreach($lead, $toEmail, 'failed', $e->getMessage());
            $lead->update(['email_status' => 'failed']);

            Log::error('OutreachService: email failed', [
                'lead_id' => $lead->id,
                'to'      => $toEmail,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function logOutreach(Lead $lead, string $email, string $status, ?string $response): void
    {
        OutreachLog::create([
            'lead_id'  => $lead->id,
            'channel'  => 'email',
            'email'    => $email,
            'status'   => $status,
            'response' => $response,
            'sent_at'  => Carbon::now(),
        ]);
    }
}
