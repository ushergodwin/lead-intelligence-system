<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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

    public function update(UpdateSettingsRequest $request): JsonResponse
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

        return response()->json(['message' => 'Settings saved successfully.']);
    }
}
