<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Http\Requests\SendEmailRequest;
use App\Jobs\SendOutreachEmailJob;
use App\Models\Lead;
use App\Models\OutreachLog;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Lead::query();

        // Archived / Active filter (default: active only)
        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where('business_name', 'like', "%{$search}%");
        }

        // Filters
        if ($request->boolean('high_score')) {
            $minScore = (int) \App\Models\Setting::get('min_ai_score', config('leads.min_ai_score', 7));
            $query->where('ai_score', '>=', $minScore);
        }

        if ($request->boolean('approved')) {
            $query->where('approved_for_outreach', true);
        }

        if ($request->boolean('contacted')) {
            $query->where('contacted', true);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Mobile-phone-only filter
        if ($request->boolean('has_mobile')) {
            $mobilePrefixes = ['077', '078', '076', '070', '075', '074'];
            $query->where(function ($q) use ($mobilePrefixes) {
                foreach ($mobilePrefixes as $prefix) {
                    $intl    = '+256' . substr($prefix, 1);
                    $intlRaw = '256'  . substr($prefix, 1);
                    $q->orWhere('phone', 'like', "{$prefix}%")
                      ->orWhere('phone', 'like', "{$intl}%")
                      ->orWhere('phone', 'like', "{$intlRaw}%");
                }
            });
        }

        // Sorting
        $sortField     = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['rating', 'reviews_count', 'ai_score', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $leads = $query->paginate(20)->withQueryString();

        $categories = Lead::select('category')->distinct()->pluck('category');

        return Inertia::render('Leads/Index', [
            'leads'      => $leads,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'high_score', 'approved', 'contacted', 'category', 'sort', 'direction', 'has_mobile', 'archived']),
        ]);
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead->load('outreachLogs'));
    }

    public function approve(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $lead->update(['approved_for_outreach' => !$lead->approved_for_outreach]);

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
