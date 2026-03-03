<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $lead->business_name }} — Website Opportunity</title>
    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            color: #333333;
            line-height: 1.7;
        }

        .wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 32px 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* Header */
        .header {
            background-color: #0d6efd;
            padding: 28px 40px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .header p {
            margin: 6px 0 0;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
        }

        /* Body */
        .body {
            padding: 36px 40px;
        }

        .body p {
            margin: 0 0 16px;
        }

        .body ul {
            margin: 0 0 16px;
            padding-left: 20px;
        }

        .body ul li {
            margin-bottom: 6px;
        }

        /* Highlight box */
        .highlight-box {
            background-color: #f0f7ff;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            padding: 14px 18px;
            margin: 20px 0;
        }

        .highlight-box strong {
            color: #0d6efd;
        }

        /* CTA button */
        .cta-wrapper {
            text-align: center;
            margin: 28px 0;
        }

        .cta-button {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 28px 0;
        }

        /* Signature */
        .signature {
            font-size: 14px;
            color: #495057;
            line-height: 1.8;
        }

        .signature strong {
            display: block;
            font-size: 15px;
            color: #212529;
        }

        .signature .title {
            color: #6c757d;
            font-size: 13px;
        }

        .signature a {
            color: #0d6efd;
            text-decoration: none;
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
            @if($companyName)
                <h1>{{ $companyName }}</h1>
                <p>Professional Web Solutions</p>
            @else
                <h1>Website Opportunity</h1>
            @endif
        </div>

        <!-- ===== BODY ===== -->
        <div class="body">

            <p>Hello <strong>{{ $lead->business_name }}</strong> Team,</p>

            <p>
                I noticed your business has strong visibility on Google with
                <strong>{{ number_format($lead->reviews_count ?? 0) }} reviews</strong>
                and a <strong>{{ $lead->rating ?? 'N/A' }} star</strong> rating — that's impressive.
            </p>

            <p>However, I couldn't find a website for your business.</p>

            <!-- Highlight box -->
            <div class="highlight-box">
                <strong>Did you know?</strong> Businesses with a website receive up to
                <strong>70% more inquiries</strong> than those without one.
            </div>

            <p>Many customers search online before calling or visiting. A simple, mobile-friendly website can help you:</p>

            <ul>
                <li>Appear more professional and trustworthy</li>
                <li>Receive more direct inquiries and bookings</li>
                <li>Increase trust from new customers</li>
                <li>Rank better in Google search results</li>
            </ul>

            <p>We specialise in helping Ugandan businesses establish a strong online presence at affordable cost.</p>

            <p>Would you be open to a quick conversation this week?</p>

            <!-- CTA button -->
            @if($companyEmail)
            <div class="cta-wrapper">
                <a href="mailto:{{ $companyEmail }}" class="cta-button">
                    Reply to Chat with Us
                </a>
            </div>
            @endif

            <hr class="divider">

            <!-- ===== SIGNATURE ===== -->
            <div class="signature">
                <p>Kind regards,</p>
                <strong>{{ $senderName }}</strong>
                @if($senderPosition)
                    <span class="title">{{ $senderPosition }}</span>
                @endif
                @if($companyName)
                    <span>{{ $companyName }}</span>
                @endif
                @if($companyEmail)
                    <span>
                        <a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>
                    </span>
                @endif
                @if($companyPhone)
                    <span>Tel: {{ $companyPhone }}</span>
                @endif
                @if($companyWhatsApp)
                    <span>WhatsApp: {{ $companyWhatsApp }}</span>
                @endif
            </div>

        </div>

        <!-- ===== FOOTER ===== -->
        <div class="footer">
            <p>
                You are receiving this email because your business was found on Google Maps without a website.<br>
                To opt out, simply reply with "unsubscribe" and we will remove you immediately.
            </p>
        </div>

    </div>
</div>
</body>
</html>
