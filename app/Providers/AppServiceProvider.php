<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('outreach-emails', function () {
            $limit = (int) Setting::get('daily_email_limit', config('leads.daily_email_limit', 20));
            return Limit::perDay($limit)->by('outreach');
        });
    }
}
