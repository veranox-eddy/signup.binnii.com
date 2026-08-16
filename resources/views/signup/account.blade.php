@extends('layouts.signup')

@section('title', 'Start your free trial')

@section('content')
    <section class="split">
        @include('signup._selling-points')
        <div class="card">
            @include('signup._steps', ['current' => 1])
            @if (session('status'))
                <p class="status-note">{{ session('status') }}</p>
            @endif
            @if (session('signup_error'))
                <p class="error-note">{{ session('signup_error') }}</p>
            @endif
            <form method="POST" action="{{ route('signup.account.store') }}" class="fform">
                @csrf
                <label>
                    <span>Your name</span>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Alex Chen" maxlength="150" required autofocus>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span>Work email</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@yourcenter.ca" maxlength="190" required>
                    @error('email')
                        <span class="field-error">
                            {{ $message }}
                            @if ($message === 'This email is already registered. Log in instead.')
                                <a href="{{ config('app.console_url') }}/login">Log in</a>
                            @endif
                        </span>
                    @enderror
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="At least 12 characters" required>
                    @error('password')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span>Country / region</span>
                    <select name="country_code" required>
                        @foreach ($countries as $country)
                            <option value="{{ $country['code'] }}" @selected(old('country_code', 'CA') === $country['code'])>{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    <span class="fhint">Sets your market and currency; your organization is bound to it at signup.</span>
                    @error('country_code')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="btn-primary">Create account &rarr;</button>
                <p class="legal">By continuing you agree to the Terms and Privacy Policy.</p>
            </form>
        </div>
    </section>
@endsection
