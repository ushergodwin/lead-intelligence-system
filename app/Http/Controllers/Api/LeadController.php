<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendEmailRequest;
use App\Jobs\SendOutreachEmailJob;
use App\Models\Lead;
use App\Models\OutreachLog;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead->load('outreachLogs'));
    }

    public function approve(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $lead->update(['approved_for_outreach' => ! $lead->approved_for_outreach]);

        return response()->json([
            'approved_for_outreach' => $lead->approved_for_outreach,
            'message'               => $lead->approved_for_outreach ? 'Lead approved.' : 'Approval revoked.',
        ]);
    }

    public function sendEmail(SendEmailRequest $request, Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        SendOutreachEmailJob::dispatch($lead, $request->validated('email'));

        return response()->json(['message' => 'Email queued successfully.']);
    }

    public function sendSms(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        if (empty($lead->phone) || ! PhoneHelper::isLikelyMobile($lead->phone)) {
            return response()->json(['message' => 'This lead does not have a valid mobile number.'], 422);
        }

        $companyName  = Setting::get('company_name', config('app.name'));
        $companyPhone = Setting::get('company_phone', '');
        $reviews      = $lead->reviews_count ?? 0;
        $reviewsLabel = $reviews === 1 ? '1 Google review' : "{$reviews} Google reviews";

        $signature = "- {$companyName}";
        if ($companyPhone) {
            $signature .= " | {$companyPhone}";
        }

        $message = "Hello {$lead->business_name}, We saw your {$reviewsLabel} - that's impressive. "
                 . "A simple website could help convert more search traffic into sales. "
                 . "Can we share a quick idea with you? {$signature}";

        $number  = PhoneHelper::normalize($lead->phone);
        $success = SmsService::send($number, $message);

        OutreachLog::create([
            'lead_id'  => $lead->id,
            'channel'  => 'sms',
            'email'    => null,
            'status'   => $success ? 'sent' : 'failed',
            'response' => $success ? 'OK' : 'Delivery failed',
            'sent_at'  => now(),
        ]);

        if ($success) {
            $lead->update(['sms_sent_at' => now()]);
            return response()->json(['message' => 'SMS sent successfully.', 'sms_sent_at' => $lead->sms_sent_at]);
        }

        return response()->json(['message' => 'SMS delivery failed. Check logs for details.'], 500);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasRole('super_admin'), 403, 'Only super admins can delete leads.');

        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully.']);
    }
}
