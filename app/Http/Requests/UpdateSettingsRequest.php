<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daily_leads_limit'   => ['required', 'integer', 'min:1', 'max:5000'],
            'daily_email_limit'   => ['required', 'integer', 'min:1', 'max:500'],
            'min_ai_score'        => ['required', 'integer', 'min:1', 'max:10'],
            'search_categories'   => ['required', 'array', 'min:1'],
            'search_categories.*' => ['required', 'string', 'max:100'],
            // Company / sender signature
            'company_name'        => ['required', 'string', 'max:150'],
            'sender_name'         => ['required', 'string', 'max:100'],
            'sender_position'     => ['required', 'string', 'max:100'],
            'company_email'       => ['required', 'email', 'max:150'],
            'company_phone'       => ['nullable', 'string', 'max:50'],
            'company_whatsapp'    => ['nullable', 'string', 'max:50'],
            // Follow-up settings
            'follow_up_days'               => ['required', 'integer', 'min:1', 'max:90'],
            'follow_up_notification_email' => ['required', 'email', 'max:150'],
        ];
    }
}
