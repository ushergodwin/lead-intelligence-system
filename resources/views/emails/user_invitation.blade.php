@extends('emails.layout')

@section('email-title', "You've been invited to " . $companyName)

@section('header')
    <h1>{{ $companyName }}</h1>
    <p>Lead Intelligence System</p>
@endsection

@section('content')
    <p>Hi <strong>{{ $user->name }}</strong>,</p>

    <p>
        You have been invited to join <strong>{{ $companyName }}</strong>'s
        Lead Intelligence System as a
        <span class="role-badge">{{ str_replace('_', ' ', $role) }}</span>.
    </p>

    <p>Click the button below to set up your password and activate your account:</p>

    <div class="cta-wrapper">
        <a href="{{ $resetUrl }}" class="cta-button">Set Up Your Account</a>
    </div>

    <div class="notice-box">
        <strong>This link expires in 60 minutes.</strong>
        If you did not expect this invitation, you can safely ignore this email.
    </div>

    <p class="url-fallback">
        If the button above doesn't work, copy and paste this URL into your browser:<br>
        {{ $resetUrl }}
    </p>
@endsection

@section('footer')
    <p>
        This invitation was sent by {{ $companyName }}.
        @if($companyEmail)
            For questions, contact <a href="mailto:{{ $companyEmail }}" style="color:#6c757d">{{ $companyEmail }}</a>.
        @endif
    </p>
@endsection
