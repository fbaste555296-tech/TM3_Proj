<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Triple-M3 Air-conditioning Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <style>
        .hero { max-width:1120px; margin:auto; padding:88px 28px 72px; }
        .hero h1 { max-width:720px; margin:16px 0; font-size:clamp(2.6rem,6vw,5.6rem); line-height:1.04; letter-spacing:-.06em; }
        .hero p { max-width:580px; font-size:18px; line-height:1.75; opacity:.78; }
        .nav-links { display:flex; align-items:center; gap:20px; font-size:13px; font-weight:700; }
        .appointment-section { padding:32px 28px 88px; background:rgba(16,31,63,.04); }
        .appointment-wrap { max-width:1120px; margin:auto; }
        .appointment-heading { display:flex; justify-content:space-between; align-items:end; gap:20px; margin-bottom:22px; }
        .appointment-heading h2 { margin:8px 0 0; font-size:30px; letter-spacing:-.04em; }
        .appointment-list { display:grid; gap:14px; }
        .appointment { display:grid; grid-template-columns:200px 1fr auto; gap:22px; align-items:center; padding:22px; }
        .appointment-date { font-size:17px; font-weight:800; line-height:1.4; }
        .appointment-time { margin-top:5px; font-size:13px; opacity:.6; }
        .appointment h3 { margin:0 0 7px; font-size:16px; }
        .appointment p { margin:0; font-size:13px; line-height:1.6; opacity:.68; }
        .status { display:inline-block; padding:7px 10px; border-radius:999px; background:rgba(26,145,136,.1); color:var(--teal); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
        .empty { padding:28px; }
        .empty p { margin:8px 0 20px; line-height:1.7; opacity:.68; }
        @media(max-width:720px) { .hero { padding:60px 22px 48px; } .appointment-section { padding:28px 22px 64px; } .appointment-heading { display:block; } .appointment-heading .tm-button { display:inline-block; margin-top:18px; } .nav-links { gap:10px; } .nav-links a:first-child { display:none; } .appointment { grid-template-columns:1fr; gap:14px; } .status { justify-self:start; } }
    </style>
</head>
<body>
    <header class="tm-topbar">
        <a href="{{ route('home') }}" class="tm-logo"><span class="tm-mark">M3</span><span>TRIPLE-M3<small class="tm-eyebrow" style="display:block">AIR-CONDITIONING SERVICES</small></span></a>
        <div class="nav-links">
            <a href="{{ route('customer.dashboard') }}">My appointments</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button style="border:0;background:none;font:inherit;font-weight:700;color:var(--navy)">Sign out</button></form>
        </div>
    </header>

    <main>
        <section class="hero">
            <span class="tm-eyebrow">FAST, RELIABLE HVAC CARE</span>
            <h1>Cooler spaces<br>start with better<br>service.</h1>
            <p>Welcome back. Your appointment details are available below, and you can request another service whenever you need it.</p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:30px">
                <a href="{{ route('appointments.create') }}" class="tm-button">Book an appointment</a>
                <a href="#appointments" class="tm-button" style="background:transparent;color:var(--navy);border:1px solid var(--navy)">View my booking</a>
            </div>
        </section>

        <section id="appointments" class="appointment-section">
            <div class="appointment-wrap">
                <div class="appointment-heading">
                    <div>
                        <p class="tm-eyebrow">YOUR SERVICE REQUESTS</p>
                        <h2>My appointments</h2>
                    </div>
                    <a href="{{ route('customer.dashboard') }}" class="tm-button" style="background:transparent;color:var(--navy);border:1px solid var(--navy)">Open full dashboard</a>
                </div>

                @if($appointments->isEmpty())
                    <div class="tm-card empty">
                        <strong>No appointments yet</strong>
                        <p>Your booked service will appear here with its date, time, and current status.</p>
                        <a href="{{ route('appointments.create') }}" class="tm-button">Book your first appointment</a>
                    </div>
                @else
                    <div class="appointment-list">
                        @foreach($appointments as $appointment)
                            <article class="tm-card appointment">
                                <div>
                                    <div class="appointment-date">{{ $appointment->scheduled_at->format('D, M j, Y') }}</div>
                                    <div class="appointment-time">{{ $appointment->scheduled_at->format('g:i A') }} - {{ $appointment->duration_minutes }} minutes</div>
                                </div>
                                <div>
                                    <h3>{{ $appointment->service_type }}</h3>
                                    <p>Request {{ $appointment->job_number }} - {{ $appointment->notes ?: 'No additional notes provided.' }}</p>
                                </div>
                                <span class="status">{{ $appointment->status }}</span>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
