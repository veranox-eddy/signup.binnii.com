@extends('layouts.signup')

@section('title', 'Check your email')

@section('content')
    <div class="card centered">
        <h1 style="font-size:22px;margin:0 0 14px">Check your email</h1>
        @if (session('status'))
            <p class="status-note">{{ session('status') }}</p>
        @endif
        <p style="font-size:13.5px;line-height:1.65;color:#3E4453;margin:0 0 18px">
            We sent a verification link to
            <b>{{ $email ?? 'your email address' }}</b>.
            Click <b>Verify email and sign in</b> in that message to start your free trial.
            The link expires in 24 hours.
        </p>
        <form method="POST" action="{{ route('signup.resend') }}" class="fform">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="btn-primary">Resend verification email</button>
        </form>
    </div>
@endsection
