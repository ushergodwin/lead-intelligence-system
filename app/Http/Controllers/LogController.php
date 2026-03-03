<?php

namespace App\Http\Controllers;

use App\Models\OutreachLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = OutreachLog::with('lead:id,business_name');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return Inertia::render('Logs/Index', [
            'logs'    => $logs,
            'filters' => $request->only(['status']),
        ]);
    }
}
