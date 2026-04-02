<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---- Scheduler ----

// Collect leads from Google Places every Monday at 1:00 AM
// Schedule::command('leads:collect')
//     ->weeklyOn(1, '01:00')
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->appendOutputTo(storage_path('logs/collect-leads.log'));

// Auto-send approved leads every day at 9:00 AM
Schedule::command('leads:send-approved')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/send-approved.log'));

// Send follow-up reminder digest to admin every day at 8:00 AM
Schedule::command('leads:send-followup-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/followup-reminders.log'));

// Auto-send SMS to approved leads and follow-ups every day at 10:00 AM
Schedule::command('leads:send-sms')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/send-sms.log'));
