@extends('emails.layout')

@section('email-title', $lead->business_name . ' — Website Opportunity')

@section('header')
    @if($companyName)
        <h1>{{ $companyName }}</h1>
        <p>Professional Web Solutions</p>
    @else
        <h1>Website Opportunity</h1>
    @endif
@endsection

@section('content')
    {!! $emailBody !!}

    @if($companyEmail)
    <div class="cta-wrapper">
        <a href="mailto:{{ $companyEmail }}" class="cta-button">Reply to Chat with Us</a>
    </div>
    @endif

    <hr class="divider">

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
            <span><a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a></span>
        @endif
        @if($companyPhone)
            <span>Tel: {{ $companyPhone }}</span>
        @endif
        @if($companyWhatsApp)
            <span>WhatsApp: {{ $companyWhatsApp }}</span>
        @endif
    </div>
@endsection

@section('footer')
    <p>
        You are receiving this email because your business was found on Google Maps without a website.<br>
        To opt out, simply reply with "unsubscribe" and we will remove you immediately.
    </p>
@endsection
