<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendEmailRequest;
use App\Jobs\SendOutreachEmailJob;
use App\Models\Lead;
use App\Models\OutreachLog;
use App\Models\Setting;
use App\Services\LeadScoringService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'category'      => ['nullable', 'string', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:150'],
            'address'       => ['nullable', 'string', 'max:500'],
        ]);

        $lead = Lead::create(array_merge($data, [
            'email_status' => 'pending',
        ]));

        return response()->json(['message' => 'Lead created.', 'lead' => $lead], 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead->load('outreachLogs'));
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:150'],
            'address'       => ['nullable', 'string', 'max:500'],
        ]);

        $lead->update($data);

        return response()->json(['message' => 'Lead updated.', 'lead' => $lead->fresh()]);
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

        $toEmail = $request->validated('email');

        // Persist the email on the lead so it's remembered for next time
        if (empty($lead->email)) {
            $lead->update(['email' => $toEmail]);
        }

        SendOutreachEmailJob::dispatch($lead, $toEmail);

        return response()->json(['message' => 'Email queued successfully.']);
    }

    public function updateNotes(Request $request, Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $request->validate(['notes' => 'nullable|string|max:5000']);

        $lead->update(['notes' => $request->input('notes')]);

        return response()->json(['message' => 'Notes saved.', 'notes' => $lead->notes]);
    }

    public function rescore(Lead $lead, LeadScoringService $scoring): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $scoring->score($lead);
        $lead->refresh();

        return response()->json(['message' => 'Lead re-scored.', 'ai_score' => $lead->ai_score]);
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

        $smsTemplate = Setting::get(
            'sms_body_template',
            "Hello {business_name}, We saw your {reviews_label} - that's impressive. A simple website could help convert more search traffic into sales. Can we share a quick idea with you? {signature}"
        );

        $message = str_replace(
            ['{business_name}', '{reviews_label}', '{signature}'],
            [$lead->business_name, $reviewsLabel, $signature],
            $smsTemplate
        );

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

    public function archive(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $lead->update(['archived_at' => now()]);

        return response()->json(['message' => 'Lead archived.', 'archived_at' => $lead->archived_at]);
    }

    public function unarchive(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $lead->update(['archived_at' => null]);

        return response()->json(['message' => 'Lead restored from archive.']);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasRole('super_admin'), 403, 'Only super admins can delete leads.');

        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully.']);
    }

    // -------------------------------------------------------------------------
    // Bulk actions
    // -------------------------------------------------------------------------

    public function bulkApprove(Request $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $count = Lead::whereIn('id', $request->ids)->update(['approved_for_outreach' => true]);

        return response()->json(['message' => "{$count} lead(s) approved.", 'count' => $count]);
    }

    public function bulkArchive(Request $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $count = Lead::whereIn('id', $request->ids)->update(['archived_at' => now()]);

        return response()->json(['message' => "{$count} lead(s) archived.", 'count' => $count]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasRole('super_admin'), 403, 'Only super admins can delete leads.');

        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $count = Lead::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => "{$count} lead(s) deleted.", 'count' => $count]);
    }

    public function exportCsv(Request $request)
    {
        $query = Lead::query()->whereNull('archived_at');

        if ($search = $request->input('search')) {
            $query->where('business_name', 'like', "%{$search}%");
        }
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }
        if ($request->boolean('approved')) {
            $query->where('approved_for_outreach', true);
        }
        if ($request->boolean('contacted')) {
            $query->where('contacted', true);
        }

        $leads = $query->orderBy('created_at', 'desc')->get();

        $filename = 'leads-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['ID', 'Business Name', 'Category', 'Address', 'Phone', 'Email', 'Rating', 'Reviews', 'AI Score', 'Approved', 'Contacted', 'Notes', 'Created At'];

        $callback = function () use ($leads, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->id,
                    $lead->business_name,
                    $lead->category,
                    $lead->address,
                    $lead->phone,
                    $lead->email,
                    $lead->rating,
                    $lead->reviews_count,
                    $lead->ai_score,
                    $lead->approved_for_outreach ? 'Yes' : 'No',
                    $lead->contacted ? 'Yes' : 'No',
                    $lead->notes,
                    $lead->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
