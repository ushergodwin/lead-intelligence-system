<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOutreachEmailJob;
use App\Models\OutreachLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    public function retry(OutreachLog $log): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        abort_if(! $authUser->hasAnyRole(['super_admin', 'manager']), 403, 'Insufficient permissions.');

        abort_if($log->channel !== 'email', 422, 'Only email logs can be retried.');
        abort_if($log->status !== 'failed', 422, 'Only failed logs can be retried.');
        abort_if(empty($log->email) || ! $log->lead, 422, 'Log is missing required email or lead data.');

        SendOutreachEmailJob::dispatch($log->lead, $log->email);

        return response()->json(['message' => 'Email re-queued for delivery.']);
    }
}
