@extends('layouts.signup')

@section('title', 'Activating your account')

@php
    // No-JS polling for the working state (§5.4): a plain meta refresh
    // re-requests this same signed URL every 3 seconds.
@endphp

@section('content')
    <div class="card centered">
        @if ($state === 'working')
            <meta http-equiv="refresh" content="3">
            <h1 style="font-size:22px;margin:0 0 14px">Activating your account&hellip;</h1>
            <p style="font-size:13.5px;line-height:1.65;color:#3E4453;margin:0 0 12px">
                This usually takes 1 minute. Please stay on this page. 
            </p>
            @if ($stalled)
                <p class="status-note">Still working on it — we'll email you when it's ready.</p>
            @endif
            <p style="font-size:12.5px;margin:0"><a href="{{ request()->fullUrl() }}">Refresh now</a></p>
        @elseif ($state === 'ready')
            <h1 style="font-size:22px;margin:0 0 14px">Your account is ready.</h1>
            <p style="font-size:13.5px;line-height:1.65;color:#3E4453;margin:0 0 18px">
                Sign in with your email and password to get started.
            </p>
            <a href="{{ config('app.console_url') }}/login" class="btn-primary" style="display:inline-block;text-decoration:none;">Log in</a>
        @elseif ($state === 'email_taken')
            <h1 style="font-size:22px;margin:0 0 14px">This email is already registered.</h1>
            <p style="font-size:13.5px;line-height:1.65;color:#3E4453;margin:0 0 18px">
                <a href="{{ config('app.console_url') }}/login">Log in to continue</a>
            </p>
        @else
            <h1 style="font-size:22px;margin:0 0 14px">We couldn't finish setting up your account.</h1>
            <p class="error-note">
                Please contact support with reference {{ $reference }}.
            </p>
        @endif
    </div>
@endsection
