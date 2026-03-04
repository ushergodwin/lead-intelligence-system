<?php

namespace App\Console\Commands;

use App\Helpers\PhoneHelper;
use App\Jobs\SendSmsJob;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SendSmsOutreach extends Command
{
    protected $signature   = 'leads:send-sms';
    protected $description = 'Dispatch SMS jobs for approved uncontacted leads and due follow-ups';

    public function handle(): int
    {
        $minScore = (int) Setting::get('min_ai_score', config('leads.min_ai_score', 7));

        // Register the rate limiter for SMS (max 30/day by default)
        $dailyLimit = (int) Setting::get('daily_sms_limit', config('leads.daily_sms_limit', 30));
        RateLimiter::for('outreach-sms', fn () => \Illuminate\Cache\RateLimiting\Limit::perDay($dailyLimit));

        // ---- Initial SMS outreach ----
        $initialLeads = Lead::readyForSmsOutreach()
            ->where('ai_score', '>=', $minScore)
            ->get();

        $initialDispatched = 0;
        foreach ($initialLeads as $lead) {
            if (empty($lead->phone) || ! PhoneHelper::isLikelyMobile($lead->phone)) {
                $this->warn("  [initial] Skipping #{$lead->id} — no valid mobile number.");
                continue;
            }

            SendSmsJob::dispatch($lead, false);
            $this->line("  [initial] Queued SMS for: {$lead->business_name}");
            $initialDispatched++;
        }

        // ---- Follow-up SMS ----
        $followUpLeads = Lead::smsFollowUpDue()->get();

        $followUpDispatched = 0;
        foreach ($followUpLeads as $lead) {
            if (empty($lead->phone) || ! PhoneHelper::isLikelyMobile($lead->phone)) {
                $this->warn("  [follow-up] Skipping #{$lead->id} — no valid mobile number.");
                continue;
            }

            SendSmsJob::dispatch($lead, true);
            $this->line("  [follow-up] Queued follow-up SMS for: {$lead->business_name}");
            $followUpDispatched++;
        }

        $this->info("Done. Initial: {$initialDispatched} queued | Follow-ups: {$followUpDispatched} queued.");

        Log::info('SendSmsOutreach: dispatched', [
            'initial'   => $initialDispatched,
            'follow_up' => $followUpDispatched,
        ]);

        return Command::SUCCESS;
    }
}
