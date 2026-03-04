<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OutreachLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $minScore = (int) \App\Models\Setting::get('min_ai_score', config('leads.min_ai_score', 7));

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total_leads'       => Lead::active()->count(),
                'high_score_leads'  => Lead::active()->highScore($minScore)->count(),
                'approved_leads'    => Lead::active()->approved()->count(),
                'contacted_leads'   => Lead::active()->contacted()->count(),
                'emails_sent_today' => OutreachLog::where('status', 'sent')
                    ->whereDate('sent_at', today())
                    ->count(),
            ],
        ]);
    }
}
