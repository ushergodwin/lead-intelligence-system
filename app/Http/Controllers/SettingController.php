<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const SIGNATURE_KEYS = [
        'company_name',
        'sender_name',
        'sender_position',
        'company_email',
        'company_phone',
        'company_whatsapp',
    ];

    public function index(): Response
    {
        $signature = [];
        foreach (self::SIGNATURE_KEYS as $key) {
            $signature[$key] = Setting::get($key, '');
        }

        return Inertia::render('Settings/Index', [
            'settings' => array_merge([
                'daily_leads_limit'            => (int) Setting::get('daily_leads_limit', config('leads.daily_leads_limit', 100)),
                'daily_email_limit'            => (int) Setting::get('daily_email_limit', config('leads.daily_email_limit', 20)),
                'daily_sms_limit'              => (int) Setting::get('daily_sms_limit', config('leads.daily_sms_limit', 30)),
                'min_ai_score'                 => (int) Setting::get('min_ai_score', config('leads.min_ai_score', 7)),
                'min_review_year'              => (int) Setting::get('min_review_year', config('leads.min_review_year', 0)),
                'search_categories'            => json_decode(
                    Setting::get('search_categories', json_encode(config('leads.default_categories'))),
                    true
                ),
                'follow_up_days'               => (int) Setting::get('follow_up_days', config('leads.follow_up_days', 4)),
                'sms_follow_up_days'           => (int) Setting::get('sms_follow_up_days', config('leads.sms_follow_up_days', 3)),
                'follow_up_notification_email' => Setting::get('follow_up_notification_email', config('leads.follow_up_notification_email', '')),
                'email_subject_template' => Setting::get('email_subject_template', "We noticed {business_name} doesn't have a website"),
                'email_body_template'    => Setting::get(
                    'email_body_template',
                    "Hello {business_name} Team,\n"
                    . "I noticed your business has strong visibility on Google with {reviews_count} reviews and a {rating} star rating — that's impressive.\n"
                    . "However, I couldn't find a website for your business.\n"
                    . "Did you know? Businesses with a website receive up to 70% more inquiries than those without one.\n"
                    . "Many customers search online before calling or visiting. A simple, mobile-friendly website can help you appear more professional, receive more direct inquiries, rank better in Google search results, and increase trust from new customers.\n"
                    . "We specialise in helping businesses establish a strong online presence at affordable cost.\n"
                    . "Would you be open to a quick conversation this week?"
                ),
                'sms_body_template' => Setting::get(
                    'sms_body_template',
                    "Hello {business_name}, We saw your {reviews_label} - that's impressive. A simple website could help convert more search traffic into sales. Can we share a quick idea with you? {signature}"
                ),
                'sms_follow_up_template' => Setting::get(
                    'sms_follow_up_template',
                    "Hi {business_name}, just following up on our message about getting you a website. We'd love to help you grow online. Feel free to reach us anytime. {signature}"
                ),
            ], $signature),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasRole('super_admin'), 403, 'Only super admins can change settings.');

        $data = $request->validated();

        Setting::set('daily_leads_limit', (string) $data['daily_leads_limit']);
        Setting::set('daily_email_limit', (string) $data['daily_email_limit']);
        Setting::set('min_ai_score',      (string) $data['min_ai_score']);
        Setting::set('search_categories', json_encode($data['search_categories']));

        foreach (self::SIGNATURE_KEYS as $key) {
            Setting::set($key, $data[$key] ?? '');
        }

        Setting::set('follow_up_days',               (string) $data['follow_up_days']);
        Setting::set('follow_up_notification_email', $data['follow_up_notification_email']);

        return back()->with('success', 'Settings saved successfully.');
    }
}
