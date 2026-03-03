<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Setting;
use App\Services\GooglePlacesService;
use App\Services\LeadScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectLeads extends Command
{
    protected $signature   = 'leads:collect {--category= : Run for a specific category only}';
    protected $description = 'Collect leads from Google Places API and score them with Claude AI';

    /**
     * Running count of NEW leads created today (wasRecentlyCreated = true).
     * Only new DB inserts count against the quota — updates to existing leads do not.
     */
    private int $newLeadsToday = 0;

    /**
     * Daily cap resolved once at startup from DB settings > .env > config default.
     */
    private int $dailyLimit = 0;

    public function __construct(
        private readonly GooglePlacesService $places,
        private readonly LeadScoringService  $scoring,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->dailyLimit    = $this->resolveDailyLimit();
        $this->newLeadsToday = Lead::whereDate('created_at', today())->count();

        $this->info("Daily leads limit : {$this->dailyLimit}");
        $this->info("New leads today   : {$this->newLeadsToday}");

        if ($this->limitReached()) {
            $this->warn("Daily limit already reached ({$this->newLeadsToday}/{$this->dailyLimit}). Nothing to do.");
            Log::warning('CollectLeads: daily limit already reached at startup.', [
                'new_today' => $this->newLeadsToday,
                'limit'     => $this->dailyLimit,
            ]);
            return Command::SUCCESS;
        }

        $categories = $this->resolveCategories();
        $this->info('Starting collection for ' . count($categories) . " category/categories.\n");
        Log::info('CollectLeads: started', ['categories' => $categories, 'limit' => $this->dailyLimit]);

        foreach ($categories as $category) {
            if ($this->limitReached()) {
                $this->warn("Daily limit reached ({$this->newLeadsToday}/{$this->dailyLimit}). Stopping early.");
                Log::info('CollectLeads: daily limit reached between categories, stopping.');
                break;
            }

            $this->processCategory($category);
        }

        $this->info("\nCollection complete. New leads today: {$this->newLeadsToday}/{$this->dailyLimit}");
        Log::info('CollectLeads: finished.', [
            'new_leads_today' => $this->newLeadsToday,
            'limit'           => $this->dailyLimit,
        ]);

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function limitReached(): bool
    {
        return $this->newLeadsToday >= $this->dailyLimit;
    }

    private function remaining(): int
    {
        return max(0, $this->dailyLimit - $this->newLeadsToday);
    }

    private function resolveDailyLimit(): int
    {
        $stored = (int) Setting::get('daily_leads_limit', 0);
        return $stored > 0 ? $stored : (int) config('leads.daily_leads_limit', 100);
    }

    private function resolveCategories(): array
    {
        if ($specific = $this->option('category')) {
            return [$specific];
        }

        $stored = Setting::get('search_categories');

        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        return config('leads.default_categories');
    }

    // -------------------------------------------------------------------------
    // Per-category processing
    // -------------------------------------------------------------------------

    private function processCategory(string $category): void
    {
        $this->line("  -> Searching: {$category}  (slots remaining: {$this->remaining()})");
        Log::info('CollectLeads: searching category', [
            'category'  => $category,
            'remaining' => $this->remaining(),
        ]);

        $places = $this->places->textSearch($category);

        if (empty($places)) {
            $this->warn("  No results for: {$category}");
            Log::warning('CollectLeads: no results', ['category' => $category]);
            return;
        }

        $this->info('  Found ' . count($places) . ' places. Processing...');

        $saved   = 0;
        $skipped = 0;

        foreach ($places as $place) {
            // Hard stop before the costly Details API call
            if ($this->limitReached()) {
                $this->warn('  Daily limit reached mid-category. Stopping to prevent excess API charges.');
                Log::info('CollectLeads: limit reached mid-category, breaking.', [
                    'category'  => $category,
                    'new_today' => $this->newLeadsToday,
                ]);
                break;
            }

            $placeId = $place['place_id'] ?? null;
            if (!$placeId) {
                continue;
            }

            // ---- Places Details API call (billed ~$0.017 each by Google) ----
            $details = $this->places->getDetails($placeId);

            if (!$details) {
                continue;
            }

            // Skip businesses that already have a website — not our target
            if (!empty($details['website'])) {
                Log::debug('CollectLeads: skipping — has website', ['place_id' => $placeId]);
                $skipped++;
                continue;
            }

            $lead = $this->storeLead($category, $details);

            if (!$lead) {
                continue;
            }

            $saved++;

            // Only new DB inserts count against the daily quota
            if ($lead->wasRecentlyCreated) {
                $this->newLeadsToday++;
                Log::info('CollectLeads: new lead counted against daily limit', [
                    'lead_id'   => $lead->id,
                    'new_today' => $this->newLeadsToday,
                    'limit'     => $this->dailyLimit,
                ]);
            }

            // Score with Claude for new leads or leads missing a score
            if ($lead->wasRecentlyCreated || is_null($lead->ai_score)) {
                $this->scoring->score($lead);
            }
        }

        $this->info(
            "  Done — saved: {$saved}, skipped (has website): {$skipped}" .
            " | new today: {$this->newLeadsToday}/{$this->dailyLimit}"
        );
        Log::info('CollectLeads: category done', [
            'category'        => $category,
            'saved'           => $saved,
            'skipped'         => $skipped,
            'new_leads_today' => $this->newLeadsToday,
        ]);
    }

    private function storeLead(string $category, array $details): ?Lead
    {
        $name    = $details['name'] ?? null;
        $address = $details['formatted_address'] ?? null;

        if (!$name || !$address) {
            return null;
        }

        try {
            return Lead::updateOrCreate(
                [
                    'business_name' => $name,
                    'address'       => $address,
                ],
                [
                    'category'        => $category,
                    'phone'           => $details['formatted_phone_number'] ?? null,
                    'google_maps_url' => $details['url'] ?? '',
                    'rating'          => $details['rating'] ?? null,
                    'reviews_count'   => $details['user_ratings_total'] ?? null,
                    'website'         => $details['website'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('CollectLeads: failed to store lead', [
                'name'    => $name,
                'address' => $address,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }
}
