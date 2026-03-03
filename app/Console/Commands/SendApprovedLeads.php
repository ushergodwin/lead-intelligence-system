<?php

namespace App\Console\Commands;

use App\Jobs\SendOutreachEmailJob;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendApprovedLeads extends Command
{
    protected $signature   = 'leads:send-approved';
    protected $description = 'Dispatch outreach email jobs for all approved, uncontacted leads';

    public function handle(): int
    {
        $minScore = (int) Setting::get('min_ai_score', config('leads.min_ai_score'));

        $leads = Lead::readyForOutreach()
            ->where('ai_score', '>=', $minScore)
            ->get();

        if ($leads->isEmpty()) {
            $this->info('No approved leads ready for outreach.');
            return Command::SUCCESS;
        }

        $this->info("Dispatching {$leads->count()} outreach jobs...");
        Log::info('SendApprovedLeads: dispatching', ['count' => $leads->count()]);

        foreach ($leads as $lead) {
            if (empty($lead->phone)) {
                $this->warn("  Skipping lead #{$lead->id} — no contact info.");
                continue;
            }

            SendOutreachEmailJob::dispatch($lead, $lead->phone);
            $this->line("  Dispatched for: {$lead->business_name}");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
