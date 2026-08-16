@extends('layouts.signup')

@section('title', 'Your organization')

@section('content')
    <section class="split">
        @include('signup._selling-points')
        <div class="card">
            @include('signup._steps', ['current' => 2])
            @if (session('signup_error'))
                <p class="error-note">{{ session('signup_error') }}</p>
            @endif
            <form method="POST" action="{{ route('signup.organization.store') }}" class="fform">
                @csrf
                <label>
                    <span>Organization name</span>
                    <input type="text" name="organization_name" value="{{ old('organization_name') }}" placeholder="Sunrise Childcare Inc." maxlength="150" required autofocus>
                    @error('organization_name')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span>Time zone</span>
                    <select name="billing_timezone" required>
                        @foreach (App\Http\Requests\SignupOrganizationRequest::TIMEZONES as $timezone)
                            <option value="{{ $timezone }}" @selected(old('billing_timezone', 'America/Vancouver') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    <span class="fhint">Your trial countdown and future billing dates use this time zone.</span>
                    @error('billing_timezone')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="btn-primary">Create organization &rarr;</button>
                <p class="legal">Your 14-day free trial starts as soon as you verify your email. No credit card required.</p>
            </form>
        </div>
    </section>
@endsection
