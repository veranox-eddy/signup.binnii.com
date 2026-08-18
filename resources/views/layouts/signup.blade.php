<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign up') — Binnii</title>
    <link rel="icon" href="/brand/binnii-mark.svg" type="image/svg+xml">
    <meta name="theme-color" content="#5E609E">
    <style>
        @font-face{font-family:'Montserrat Variable';font-style:normal;font-weight:100 900;font-display:swap;src:url('/brand/fonts/montserrat-latin-wght-normal.woff2') format('woff2-variations')}
        body{margin:0;background:#F4F2F5;color:#1F2430;font-family:'Montserrat Variable',Montserrat,'Noto Sans TC',-apple-system,'Segoe UI',Roboto,sans-serif;font-size:14px;-webkit-font-smoothing:antialiased}
        a{color:#5E609E}a:hover{color:#45477A}
        .topnav{display:flex;align-items:center;gap:18px;padding:14px clamp(20px,5vw,64px);border-bottom:1px solid #E2E2E9;background:#fff;flex-wrap:wrap}
        .topnav .brand{margin-right:auto;display:flex}
        .topnav .hint{font-size:13px;color:#6B7280}
        .topnav .login{color:#5E609E;text-decoration:none;font-size:13px;font-weight:600;white-space:nowrap;border:1.5px solid #5E609E;border-radius:5px;padding:6.5px 15px}
        .topnav .login:hover{background:#5E609E;color:#fff}
        .shell{max-width:1080px;margin:0 auto;padding:0 clamp(20px,5vw,64px)}
        .split{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:48px clamp(28px,6vw,90px);align-items:start;padding:64px 0 72px}
        .split h1{font-size:clamp(28px,3.5vw,36px);font-weight:700;letter-spacing:-.02em;line-height:1.25;margin:0}
        .points{display:flex;flex-direction:column;gap:14px;margin-top:26px;font-size:14px;line-height:1.65}
        .points p{margin:0}
        .points span{color:#3E4453}
        .notecard{background:#EEEEF2;border-radius:6px;padding:14px 16px;margin-top:26px;max-width:48ch}
        .notecard p{font-size:13px;line-height:1.65;color:#3E4453;margin:0}
        .card{background:#fff;border:1px solid #E2E2E9;border-radius:8px;padding:28px 26px;max-width:460px;box-shadow:0 8px 24px rgba(42,43,71,.08)}
        .card.centered{margin:64px auto 72px}
        .steps{display:flex;gap:16px;margin:0 0 22px;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
        .steps .on{color:#5E609E;border-bottom:2px solid #E77856;padding-bottom:3px}
        .steps .off{color:#9AA0AE}
        .fform{display:flex;flex-direction:column;gap:16px}
        .fform label{display:flex;flex-direction:column;gap:6px}
        .fform label>span{font-size:12.5px;font-weight:600;color:#3E4453}
        .fform input,.fform select{font:inherit;font-size:13.5px;color:#1F2430;border:1px solid #CFD0DC;border-radius:5px;padding:9px 11px;background:#fff}
        .fform input:focus,.fform select:focus{border-color:#5E609E;outline:2px solid #EEEEF2}
        .fhint{font-size:11.5px;line-height:1.5;color:#6B7280}
        /* Outranks .fform label>span, which otherwise recolors the error grey. */
        .fform label>span.field-error{font-size:12px;font-weight:500;color:#9D1500}
        .status-note{background:#EEEEF2;border-radius:6px;padding:10px 12px;font-size:13px;color:#3E4453;margin:0 0 16px}
        .error-note{background:#FBEDEA;border-radius:6px;padding:10px 12px;font-size:13px;color:#9D1500;margin:0 0 16px}
        .btn-primary{font:inherit;font-size:14px;font-weight:600;color:#fff;background:#5E609E;border:0;border-radius:5px;padding:11px 16px;cursor:pointer}
        .btn-primary:hover{background:#45477A}
        .legal{font-size:11.5px;line-height:1.5;color:#6B7280;margin:0;text-align:center}
        .sitefooter{background:#22233A;color:rgba(255,255,255,.7)}
        .sitefooter .inner{max-width:1080px;margin:0 auto;padding:30px clamp(20px,5vw,64px);display:flex;align-items:center;gap:26px;flex-wrap:wrap;font-size:12.5px}
        .sitefooter img{margin-right:auto}
        .sitefooter a{color:rgba(255,255,255,.7);text-decoration:none}
        .sitefooter a:hover{color:#fff}
    </style>
</head>
<body>
    <nav class="topnav">
        <a href="{{ config('app.website_url') }}" class="brand"><img src="/brand/binnii-logo.svg" alt="Binnii" style="width:132px;height:auto;display:block"></a>
        <span class="hint">Already have an account?</span>
        <a href="{{ config('app.console_url') }}/login" class="login">Log in</a>
    </nav>
    <div class="shell">
        @yield('content')
    </div>
    <footer class="sitefooter">
        <div class="inner">
            <img src="/brand/binnii-logo-reverse.svg" alt="Binnii" style="width:110px;height:auto">
            <a href="{{ config('app.website_url') }}/FAQ.dc.html">Help</a>
            <span>Terms</span><span>Privacy</span><span>© Binnii by Haody</span>
        </div>
    </footer>
</body>
</html>
