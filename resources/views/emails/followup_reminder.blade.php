<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-Up Reminder</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            color: #333333;
            line-height: 1.6;
        }

        .wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 32px 0;
        }

        .container {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* Header */
        .header {
            background-color: #fd7e14;
            padding: 24px 40px;
        }

        .header h1 {
            margin: 0 0 4px;
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            color: rgba(255,255,255,0.85);
            font-size: 13px;
        }

        /* Body */
        .body {
            padding: 32px 40px;
        }

        .body p {
            margin: 0 0 16px;
        }

        /* Summary badge */
        .summary {
            display: inline-block;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 10px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #856404;
        }

        .summary strong {
            font-size: 22px;
            color: #fd7e14;
            display: block;
            line-height: 1.2;
        }

        /* Lead card */
        .lead-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 12px;
            background-color: #fafafa;
        }

        .lead-card:last-child {
            margin-bottom: 0;
        }

        .lead-name {
            font-size: 16px;
            font-weight: 700;
            color: #212529;
            margin: 0 0 6px;
        }

        .lead-meta {
            font-size: 13px;
            color: #6c757d;
            margin: 0 0 10px;
        }

        .lead-meta span {
            margin-right: 14px;
        }

        .lead-meta i-emu {
            margin-right: 4px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-orange { background-color: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .badge-blue   { background-color: #cfe2ff; color: #084298; border: 1px solid #9ec5fe; }

        .lead-actions {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-primary { background-color: #0d6efd; color: #ffffff !important; }
        .btn-outline { background-color: #ffffff; color: #fd7e14 !important; border: 1px solid #fd7e14; }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 28px 0;
        }

        /* CTA */
        .cta-box {
            background-color: #f0f7ff;
            border-radius: 8px;
            padding: 20px 24px;
            text-align: center;
            margin-top: 24px;
        }

        .cta-box p {
            margin: 0 0 12px;
            font-size: 14px;
            color: #495057;
        }

        .cta-box a {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff !important;
            text-decoration: none;
            padding: 11px 28px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
        }

        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 18px 40px;
            text-align: center;
            font-size: 11px;
            color: #adb5bd;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <!-- ===== HEADER ===== -->
        <div class="header">
            <h1>&#9200; Follow-Up Reminders</h1>
            <p>{{ $appName }} &mdash; {{ now()->format('l, F j, Y') }}</p>
        </div>

        <!-- ===== BODY ===== -->
        <div class="body">

            <p>Hi there,</p>

            <p>
                The following
                @if($leads->count() === 1)
                    business was
                @else
                    <strong>{{ $leads->count() }} businesses were</strong>
                @endif
                contacted via outreach and are now due for a follow-up.
                Reaching out again increases your conversion rate significantly.
            </p>

            <!-- Summary badge -->
            <div class="summary">
                <strong>{{ $leads->count() }}</strong>
                {{ Str::plural('business', $leads->count()) }} due for follow-up today
            </div>

            <!-- ===== LEAD CARDS ===== -->
            @foreach($leads as $lead)
            <div class="lead-card">
                <div class="lead-name">{{ $lead->business_name }}</div>

                <div class="lead-meta">
                    @if($lead->phone)
                        <span>&#128222; {{ $lead->phone }}</span>
                    @endif
                    @if($lead->rating)
                        <span>&#11088; {{ $lead->rating }} ({{ number_format($lead->reviews_count ?? 0) }} reviews)</span>
                    @endif
                    <span>&#128205; {{ $lead->address }}</span>
                </div>

                <div>
                    <span class="badge badge-orange">
                        Outreach sent: {{ $lead->follow_up_due_at?->subDays($followUpDays)->format('M j, Y') ?? '—' }}
                    </span>
                    @if($lead->ai_score)
                        <span class="badge badge-blue" style="margin-left:6px">AI Score: {{ $lead->ai_score }}/10</span>
                    @endif
                </div>

                <div class="lead-actions">
                    @if($lead->google_maps_url)
                        <a href="{{ $lead->google_maps_url }}" class="btn btn-outline" target="_blank">
                            &#128506; View on Maps
                        </a>
                    @endif
                    <a href="{{ $appUrl }}/leads?search={{ urlencode($lead->business_name) }}" class="btn btn-primary">
                        Open in Dashboard
                    </a>
                </div>
            </div>
            @endforeach

            <!-- CTA -->
            <div class="cta-box">
                <p>View all leads and manage your outreach from the dashboard.</p>
                <a href="{{ $appUrl }}/leads?contacted=1">Go to Contacted Leads &rarr;</a>
            </div>

            <hr class="divider">

            <p style="font-size:13px; color:#6c757d; margin:0;">
                This reminder was automatically generated by {{ $appName }}.<br>
                You are receiving this because you have outreach reminders enabled in settings.
            </p>

        </div>

        <!-- ===== FOOTER ===== -->
        <div class="footer">
            <p>
                &copy; {{ now()->year }} {{ $appName }}. Internal system notification.<br>
                To adjust reminder settings, visit your <a href="{{ $appUrl }}/settings" style="color:#0d6efd;">Settings</a> page.
            </p>
        </div>

    </div>
</div>
</body>
</html>
