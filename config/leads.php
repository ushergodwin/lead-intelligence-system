<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Places API
    |--------------------------------------------------------------------------
    */
    'google_places_api_key' => env('GOOGLE_PLACES_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Anthropic / Claude API
    |--------------------------------------------------------------------------
    */
    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    'anthropic_model'   => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),

    /*
    |--------------------------------------------------------------------------
    | Search Categories
    | Defaults here — overridable from the Settings page stored in DB.
    |--------------------------------------------------------------------------
    */
    'default_categories' => [
        // Tourism
        'tour operators Kampala',
        'travel agencies Kampala',
        'safari companies Uganda',
        // Logistics
        'logistics company Kampala',
        'clearing and forwarding Kampala',
        'delivery services Kampala',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Collection Limits
    | Controls how many NEW leads are stored per day, capping Google API cost.
    | Each new lead = 1 Places Details API call ($0.017 each). Example:
    |   100 leads/day ≈ $1.70/day ≈ $51/month
    |--------------------------------------------------------------------------
    */
    'daily_leads_limit' => (int) env('LEADS_DAILY_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Outreach Limits
    |--------------------------------------------------------------------------
    */
    'daily_email_limit' => (int) env('OUTREACH_DAILY_LIMIT', 20),
    'daily_sms_limit'   => (int) env('OUTREACH_SMS_DAILY_LIMIT', 30),
    'min_delay_seconds' => (int) env('OUTREACH_MIN_DELAY', 20),
    'max_delay_seconds' => (int) env('OUTREACH_MAX_DELAY', 60),
    'min_ai_score'      => (int) env('OUTREACH_MIN_AI_SCORE', 7),

    /*
    |--------------------------------------------------------------------------
    | Follow-Up Settings
    |--------------------------------------------------------------------------
    */
    'follow_up_days'               => (int) env('FOLLOW_UP_DAYS', 4),
    'sms_follow_up_days'           => (int) env('SMS_FOLLOW_UP_DAYS', 3),
    'follow_up_notification_email' => env('FOLLOW_UP_NOTIFICATION_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Lead Filtering
    |--------------------------------------------------------------------------
    */
    'min_review_year'   => (int) env('LEADS_MIN_REVIEW_YEAR', 0),
    'min_reviews_count' => (int) env('LEADS_MIN_REVIEWS_COUNT', 10),

];
