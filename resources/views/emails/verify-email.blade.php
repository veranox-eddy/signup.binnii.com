<p>Hi {{ $pending->name }},</p>

<p>
    Your organization <b>{{ $pending->organization_name }}</b> is ready to be set up on {{ config('app.name') }}.
    Verify your email to start your 14-day free trial.
</p>

<p>
    <a href="{{ route('signup.verify', $plainToken) }}"
       style="display:inline-block;background:#5E609E;color:#ffffff;text-decoration:none;font-weight:600;border-radius:5px;padding:11px 18px;font-family:Montserrat,'Noto Sans TC',sans-serif;">
        Verify email and sign in
    </a>
</p>

<p>This link expires in 24 hours.</p>

<p>{{ config('app.name') }}</p>
