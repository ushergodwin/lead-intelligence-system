<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('email-title', config('app.name'))</title>
    <style>
        /* ===== Email Reset ===== */
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

        /* ===== Wrapper & Container ===== */
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

        /* ===== Header ===== */
        .header {
            background-color: #0f172a;
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
            color: rgba(255,255,255,0.6);
            font-size: 13px;
        }

        /* ===== Body ===== */
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

        /* ===== CTA Button ===== */
        .cta-wrapper {
            text-align: center;
            margin: 28px 0;
        }

        .cta-button {
            display: inline-block;
            background-color: #1d4ed8;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 700;
        }

        /* ===== Highlight / Info box ===== */
        .highlight-box {
            background-color: #f0f7ff;
            border-left: 4px solid #1d4ed8;
            border-radius: 4px;
            padding: 14px 18px;
            margin: 20px 0;
        }

        .highlight-box strong { color: #1d4ed8; }

        .notice-box {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
            margin: 20px 0;
        }

        /* ===== Divider ===== */
        .divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 28px 0;
        }

        /* ===== Signature ===== */
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

        .signature a { color: #1d4ed8; text-decoration: none; }

        /* ===== Footer ===== */
        .footer {
            background-color: #f8f9fa;
            padding: 18px 40px;
            text-align: center;
            font-size: 11px;
            color: #adb5bd;
            border-top: 1px solid #e9ecef;
        }

        /* ===== Misc ===== */
        .role-badge {
            display: inline-block;
            background-color: #1d4ed8;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
            letter-spacing: 0.4px;
            text-transform: capitalize;
        }

        .url-fallback {
            word-break: break-all;
            font-size: 12px;
            color: #6c757d;
        }

        @yield('extra-styles')
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <!-- ===== HEADER ===== -->
        <div class="header">
            @yield('header')
        </div>

        <!-- ===== BODY ===== -->
        <div class="body">
            @yield('content')
        </div>

        <!-- ===== FOOTER ===== -->
        <div class="footer">
            @yield('footer')
        </div>

    </div>
</div>
</body>
</html>
