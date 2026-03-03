<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Setting;
use App\Services\OutreachService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SendOutreachEmailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum job attempts before failing.
     */
    public int $tries = 3;

    /**
     * Seconds before the job times out.
     */
    public int $timeout = 120;

    /**
     * Unique key to prevent duplicate jobs per lead.
     */
    public function uniqueId(): string
    {
        return (string) $this->lead->id;
    }

    public function __construct(
        public readonly Lead   $lead,
        public readonly string $toEmail,
    ) {}

    /**
     * Apply rate-limiting middleware (shared "outreach-emails" limiter).
     */
    public function middleware(): array
    {
        return [new RateLimited('outreach-emails')];
    }

    public function handle(OutreachService $outreach): void
    {
        // Re-check daily limit inside the job (defensive guard)
        if ($outreach->dailyLimitReached()) {
            Log::info('SendOutreachEmailJob: daily limit reached, releasing.', [
                'lead_id' => $this->lead->id,
            ]);
            $this->release(3600); // re-queue after 1 hour
            return;
        }

        // Random human-like delay
        $delay = rand(
            (int) Setting::get('outreach_min_delay', config('leads.min_delay_seconds')),
            (int) Setting::get('outreach_max_delay', config('leads.max_delay_seconds'))
        );

        Log::info("SendOutreachEmailJob: sleeping {$delay}s before sending.", [
            'lead_id' => $this->lead->id,
        ]);

        sleep($delay);

        $outreach->sendEmail($this->lead, $this->toEmail);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendOutreachEmailJob: permanently failed', [
            'lead_id' => $this->lead->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
