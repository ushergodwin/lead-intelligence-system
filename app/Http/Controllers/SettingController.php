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
                'min_ai_score'                 => (int) Setting::get('min_ai_score', config('leads.min_ai_score', 7)),
                'search_categories'            => json_decode(
                    Setting::get('search_categories', json_encode(config('leads.default_categories'))),
                    true
                ),
                'follow_up_days'               => (int) Setting::get('follow_up_days', config('leads.follow_up_days', 4)),
                'follow_up_notification_email' => Setting::get('follow_up_notification_email', config('leads.follow_up_notification_email', '')),
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
