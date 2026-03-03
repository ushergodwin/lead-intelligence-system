<?php

namespace App\Console\Commands;

use App\Mail\FollowUpReminderEmail;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFollowUpReminders extends Command
{
    protected $signature   = 'leads:send-followup-reminders';
    protected $description = 'Send follow-up reminder digest emails to the admin for all leads due for follow-up';

    public function handle(): int
    {
        $notificationEmail = Setting::get('follow_up_notification_email');

        if (empty($notificationEmail)) {
            $this->warn('No follow-up notification email configured. Set one in Settings → Follow-Up Settings.');
            Log::warning('SendFollowUpReminders: follow_up_notification_email not configured, skipping.');
            return Command::SUCCESS;
        }

        $leads = Lead::followUpDue()->get();

        if ($leads->isEmpty()) {
            $this->info('No leads due for follow-up today.');
            Log::info('SendFollowUpReminders: no leads due.');
            return Command::SUCCESS;
        }

        $this->info("Found {$leads->count()} lead(s) due for follow-up. Sending digest to: {$notificationEmail}");
        Log::info('SendFollowUpReminders: sending digest.', [
            'count'  => $leads->count(),
            'to'     => $notificationEmail,
            'leads'  => $leads->pluck('id'),
        ]);

        try {
            Mail::to($notificationEmail)->send(new FollowUpReminderEmail($leads));

            // Mark all included leads as follow-up sent
            Lead::whereIn('id', $leads->pluck('id'))
                ->update(['follow_up_sent' => true]);

            $this->info('Digest sent and leads marked as follow-up sent.');
            Log::info('SendFollowUpReminders: digest sent successfully.', ['to' => $notificationEmail]);

        } catch (\Throwable $e) {
            $this->error("Failed to send follow-up reminder: {$e->getMessage()}");
            Log::error('SendFollowUpReminders: failed to send digest.', [
                'to'    => $notificationEmail,
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
