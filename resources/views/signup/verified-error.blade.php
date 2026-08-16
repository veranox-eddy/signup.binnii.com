@extends('layouts.signup')

@section('title', 'Verification')

@section('content')
    <div class="card centered">
        <h1 style="font-size:22px;margin:0 0 14px">{{ $title }}</h1>
        @if (session('status'))
            <p class="status-note">{{ session('status') }}</p>
        @endif
        @if ($showResend)
            <form method="POST" action="{{ route('signup.resend') }}" class="fform">
                @csrf
                <label>
                    <span>Work email</span>
                    <input type="email" name="email" maxlength="190" required>
                </label>
                <button type="submit" class="btn-primary">Resend verification email</button>
            </form>
        @else
            <p style="font-size:13.5px;line-height:1.65;color:#3E4453;margin:0 0 18px">
                <a href="{{ config('app.console_url') }}/login">Log in to continue</a>
            </p>
        @endif
    </div>
@endsection
