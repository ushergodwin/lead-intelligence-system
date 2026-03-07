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

    /**
     * Minimum review year for a lead to be collected (0 = no filter).
     */
    private int $minReviewYear = 0;

    /**
     * Minimum number of Google reviews required to collect a lead.
     */
    private int $minReviewsCount = 10;

    public function handle(): int
    {
        $this->dailyLimit      = $this->resolveDailyLimit();
        $this->newLeadsToday   = Lead::whereDate('created_at', today())->count();
        $this->minReviewYear   = (int) Setting::get('min_review_year', 0);
        $this->minReviewsCount = (int) Setting::get('min_reviews_count', config('leads.min_reviews_count', 10));

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

        $categories       = $this->resolveCategories();
        $categoryCount    = count($categories);
        $perCategoryLimit = $categoryCount > 0
            ? (int) ceil($this->dailyLimit / $categoryCount)
            : $this->dailyLimit;

        $this->info('Starting collection for ' . $categoryCount . " category/categories.");
        $this->info("Per-category limit : {$perCategoryLimit}\n");
        Log::info('CollectLeads: started', [
            'categories'        => $categories,
            'limit'             => $this->dailyLimit,
            'per_category_limit' => $perCategoryLimit,
        ]);

        foreach ($categories as $category) {
            if ($this->limitReached()) {
                $this->warn("Daily limit reached ({$this->newLeadsToday}/{$this->dailyLimit}). Stopping early.");
                Log::info('CollectLeads: daily limit reached between categories, stopping.');
                break;
            }

            $this->processCategory($category, $perCategoryLimit);
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

    private function processCategory(string $category, int $perCategoryLimit): void
    {
        $this->line("  -> Searching: {$category}  (slots remaining: {$this->remaining()}, cat limit: {$perCategoryLimit})");
        Log::info('CollectLeads: searching category', [
            'category'          => $category,
            'remaining'         => $this->remaining(),
            'per_category_limit' => $perCategoryLimit,
        ]);

        // Fetch at most 3× the per-category limit as candidates (allows for skips/duplicates)
        $candidateCap = $perCategoryLimit * 3;
        $places = $this->places->textSearch($category, $candidateCap);

        if (empty($places)) {
            $this->warn("  No results for: {$category}");
            Log::warning('CollectLeads: no results', ['category' => $category]);
            return;
        }

        // Sort by rating DESC, then review count DESC — highest-value leads first
        usort($places, function (array $a, array $b) {
            $ratingCmp = ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            if ($ratingCmp !== 0) {
                return $ratingCmp;
            }
            return ($b['user_ratings_total'] ?? 0) <=> ($a['user_ratings_total'] ?? 0);
        });

        $this->info('  Found ' . count($places) . ' places (sorted by rating/reviews). Processing...');

        $saved       = 0;
        $skipped     = 0;
        $categoryNew = 0;

        foreach ($places as $place) {
            // Hard stop: global daily limit OR per-category cap
            if ($this->limitReached()) {
                $this->warn('  Daily limit reached mid-category. Stopping.');
                Log::info('CollectLeads: daily limit reached mid-category.', [
                    'category'  => $category,
                    'new_today' => $this->newLeadsToday,
                ]);
                break;
            }

            if ($categoryNew >= $perCategoryLimit) {
                $this->warn("  Per-category limit ({$perCategoryLimit}) reached for: {$category}");
                Log::info('CollectLeads: per-category limit reached.', [
                    'category'          => $category,
                    'category_new'      => $categoryNew,
                    'per_category_limit' => $perCategoryLimit,
                ]);
                break;
            }

            $placeId = $place['place_id'] ?? null;
            if (!$placeId) {
                continue;
            }

            // Skip businesses with fewer reviews than the minimum threshold
            $reviewCount = (int) ($place['user_ratings_total'] ?? 0);
            if ($reviewCount < $this->minReviewsCount) {
                Log::debug('CollectLeads: skipping — insufficient reviews', [
                    'place_id'         => $placeId,
                    'reviews'          => $reviewCount,
                    'min_reviews_count' => $this->minReviewsCount,
                ]);
                $skipped++;
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

            // Skip leads whose most recent review is older than the configured minimum year
            if ($this->minReviewYear > 0) {
                $lastReviewYear = $this->extractLastReviewYear($details);
                if ($lastReviewYear !== null && $lastReviewYear < $this->minReviewYear) {
                    Log::debug('CollectLeads: skipping — review too old', [
                        'place_id'        => $placeId,
                        'last_review_year' => $lastReviewYear,
                        'min_review_year'  => $this->minReviewYear,
                    ]);
                    $skipped++;
                    continue;
                }
            }

            $lead = $this->storeLead($category, $details);

            if (!$lead) {
                continue;
            }

            $saved++;

            // Only new DB inserts count against the daily quota and category cap
            if ($lead->wasRecentlyCreated) {
                $this->newLeadsToday++;
                $categoryNew++;
                Log::info('CollectLeads: new lead counted against limits', [
                    'lead_id'      => $lead->id,
                    'new_today'    => $this->newLeadsToday,
                    'category_new' => $categoryNew,
                    'limit'        => $this->dailyLimit,
                ]);
            }

            // Score with Claude for new leads or leads missing a score
            if ($lead->wasRecentlyCreated || is_null($lead->ai_score)) {
                $this->scoring->score($lead);
            }
        }

        $this->info(
            "  Done — saved: {$saved}, skipped (has website): {$skipped}" .
            " | new this category: {$categoryNew}/{$perCategoryLimit}" .
            " | new today: {$this->newLeadsToday}/{$this->dailyLimit}"
        );
        Log::info('CollectLeads: category done', [
            'category'          => $category,
            'saved'             => $saved,
            'skipped'           => $skipped,
            'category_new'      => $categoryNew,
            'per_category_limit' => $perCategoryLimit,
            'new_leads_today'   => $this->newLeadsToday,
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
                    'category'         => $category,
                    'phone'            => $details['formatted_phone_number'] ?? null,
                    'google_maps_url'  => $details['url'] ?? '',
                    'rating'           => $details['rating'] ?? null,
                    'reviews_count'    => $details['user_ratings_total'] ?? null,
                    'last_review_year' => $this->extractLastReviewYear($details),
                    'website'          => $details['website'] ?? null,
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

    /**
     * Extract the year of the most recent review from the Places Details response.
     * The reviews array contains objects with a `time` field (Unix timestamp).
     * Google returns up to 5 most recent reviews; we take the max timestamp.
     */
    private function extractLastReviewYear(array $details): ?int
    {
        $reviews = $details['reviews'] ?? [];

        if (empty($reviews)) {
            return null;
        }

        $maxTime = 0;
        foreach ($reviews as $review) {
            $time = $review['time'] ?? 0;
            if ($time > $maxTime) {
                $maxTime = $time;
            }
        }

        return $maxTime > 0 ? (int) date('Y', $maxTime) : null;
    }
}
