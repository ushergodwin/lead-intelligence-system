<?php

namespace App\Jobs;

use App\Helpers\PhoneHelper;
use App\Models\Lead;
use App\Models\OutreachLog;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /**
     * Whether this is a follow-up SMS (vs. initial outreach).
     */
    public function __construct(
        public readonly Lead $lead,
        public readonly bool $isFollowUp = false,
    ) {}

    /**
     * Unique key — one job per lead per type (initial vs follow-up).
     */
    public function uniqueId(): string
    {
        return $this->lead->id . '-' . ($this->isFollowUp ? 'followup' : 'initial');
    }

    public function middleware(): array
    {
        return [new RateLimited('outreach-sms')];
    }

    public function handle(): void
    {
        // Check daily SMS limit
        $dailyLimit = (int) Setting::get('daily_sms_limit', config('leads.daily_sms_limit', 30));
        $sentToday  = OutreachLog::where('channel', 'sms')
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        if ($sentToday >= $dailyLimit) {
            Log::info('SendSmsJob: daily SMS limit reached, releasing.', [
                'lead_id'     => $this->lead->id,
                'sent_today'  => $sentToday,
                'daily_limit' => $dailyLimit,
            ]);
            $this->release(3600);
            return;
        }

        // Guard: skip if lead is now archived or invalid
        $lead = $this->lead->fresh();
        if (!$lead || $lead->archived_at) {
            return;
        }

        // For follow-up: skip if already sent
        if ($this->isFollowUp && $lead->sms_follow_up_sent) {
            return;
        }

        // For initial: skip if already contacted via SMS
        if (!$this->isFollowUp && $lead->sms_sent_at) {
            return;
        }

        if (empty($lead->phone) || ! PhoneHelper::isLikelyMobile($lead->phone)) {
            Log::info('SendSmsJob: no valid mobile number, skipping.', ['lead_id' => $lead->id]);
            return;
        }

        // Build message
        $message = $this->buildMessage($lead);
        $number  = PhoneHelper::normalize($lead->phone);

        // Random human-like delay
        $delay = rand(
            (int) Setting::get('outreach_min_delay', config('leads.min_delay_seconds', 20)),
            (int) Setting::get('outreach_max_delay', config('leads.max_delay_seconds', 60))
        );
        sleep($delay);

        $success = SmsService::send($number, $message);

        OutreachLog::create([
            'lead_id'  => $lead->id,
            'channel'  => 'sms',
            'email'    => null,
            'status'   => $success ? 'sent' : 'failed',
            'response' => $success ? 'OK' : 'Delivery failed',
            'sent_at'  => now(),
        ]);

        if ($success) {
            if ($this->isFollowUp) {
                $lead->update(['sms_follow_up_sent' => true]);
                Log::info('SendSmsJob: follow-up SMS sent', ['lead_id' => $lead->id]);
            } else {
                $followUpDays = (int) Setting::get('sms_follow_up_days', config('leads.sms_follow_up_days', 3));
                $lead->update([
                    'contacted'           => true,
                    'sms_sent_at'         => now(),
                    'sms_follow_up_due_at' => Carbon::now()->addDays($followUpDays),
                    'sms_follow_up_sent'  => false,
                ]);
                Log::info('SendSmsJob: initial SMS sent', [
                    'lead_id'          => $lead->id,
                    'follow_up_due_at' => $lead->sms_follow_up_due_at,
                ]);
            }
        } else {
            Log::error('SendSmsJob: SMS delivery failed', ['lead_id' => $lead->id]);
        }
    }

    private function buildMessage(Lead $lead): string
    {
        $companyName  = Setting::get('company_name', config('app.name'));
        $senderName   = Setting::get('sender_name', '');
        $companyPhone = Setting::get('company_phone', '');
        $whatsapp     = Setting::get('company_whatsapp', '');
        $reviews      = $lead->reviews_count ?? 0;
        $reviewsLabel = $reviews === 1 ? '1 Google review' : "{$reviews} Google reviews";

        // Sender identity
        $signature = $senderName ? "- {$senderName}" : "- {$companyName}";

        // Call-to-action lines
        $ctaParts = [];
        if ($companyPhone) {
            $ctaParts[] = "Call: {$companyPhone}";
        }
        if ($whatsapp) {
            $waNumber   = preg_replace('/\D/', '', $whatsapp);
            $ctaParts[] = "WhatsApp: https://wa.me/{$waNumber}";
        }
        $cta = implode("\n", $ctaParts);

        if ($this->isFollowUp) {
            $template = Setting::get(
                'sms_follow_up_template',
                "Hi {business_name}, just following up on our message about getting you a website. We'd love to help you grow online.\n{signature}\n{cta}"
            );
        } else {
            $template = Setting::get(
                'sms_body_template',
                "Hello {business_name}, We saw your {reviews_label} - that's impressive. A simple website could help you get more clients. Interested?\n{signature}\n{cta}"
            );
        }

        return str_replace(
            ['{business_name}', '{reviews_label}', '{signature}', '{cta}'],
            [$lead->business_name, $reviewsLabel, $signature, $cta],
            $template
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob: permanently failed', [
            'lead_id'     => $this->lead->id,
            'is_followup' => $this->isFollowUp,
            'error'       => $exception->getMessage(),
        ]);
    }
}
