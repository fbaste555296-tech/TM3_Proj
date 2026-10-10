
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Triple-M3 Air-conditioning Services</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <link href="{{ asset('css/triple-m3-theme.css') }}" rel="stylesheet">

    <style>
        :root {
            --bg: #f6f5f3;
            --card: #ffffff;
            --panel: #eeecea;
            --primary: #88736d;
            --primary-dark: #5e504c;
            --ink: #242323;
            --muted: #6f6b69;
            --line: #dedbd8;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
        }

        a { text-decoration: none; }
        button, input, select, textarea { font: inherit; }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 18px 56px;
            background: rgba(255,255,255,0.7);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--ink);
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            object-fit: contain;
            border-radius: 50%;
            background: var(--white);
        }

        .brand-copy {
            font-weight: 800;
            letter-spacing: -0.04em;
            font-size: 19px;
            line-height: 1;
        }

        .brand-copy small {
            display: block;
            margin-top: 3px;
            font-size: 10px;
            letter-spacing: 0.15em;
            font-weight: 700;
            color: var(--primary);
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
        }

        .nav a {
            color: var(--ink);
        }

        .nav .btn-primary {
            background: var(--primary);
            color: var(--white);
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
        }

        .page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 62px 56px 40px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            align-items: center;
            min-height: 620px;
            gap: 32px;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(136, 115, 109, 0.12);
            color: var(--primary);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            margin: 28px 0 26px;
            font-size: clamp(4.2rem, 7vw, 9.8rem);
            line-height: 0.88;
            letter-spacing: -0.08em;
            color: var(--ink);
            font-weight: 800;
        }

        h1 .accent {
            display: block;
            color: var(--primary);
        }

        .lead {
            max-width: 660px;
            font-size: 20px;
            line-height: 1.7;
            color: var(--muted);
            margin: 0 0 28px;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 14px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 28px;
            border-radius: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .button.primary {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 12px 24px rgba(136, 115, 109, 0.18);
        }

        .button.secondary {
            background: transparent;
            border-color: rgba(36, 35, 35, 0.5);
            color: var(--ink);
        }

        .promo-visual {
            position: relative;
            width: min(480px, 100%);
            aspect-ratio: 1;
            margin-left: auto;
            display: grid;
            place-items: center;
        }

        .promo-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(222, 219, 216, 0.55);
        }

        .promo-ring::before {
            content: "";
            position: absolute;
            inset: 42px;
            border-radius: 50%;
            border: 26px solid rgba(136, 115, 109, 0.18);
        }

        .snowflake {
            position: relative;
            width: 110px;
            height: 110px;
            color: var(--primary);
            font-size: 62px;
            display: grid;
            place-items: center;
            z-index: 1;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: rgba(255,255,255,0.25);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 30px 24px 22px;
            box-shadow: 0 10px 25px rgba(36, 35, 35, 0.04);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(136, 115, 109, 0.09);
            color: var(--primary);
            font-size: 22px;
            margin-bottom: 18px;
        }

        .feature-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: var(--ink);
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 14px;
        }

        .appointments {
            margin-top: 38px;
            background: rgba(36, 35, 35, 0.04);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 26px 28px;
        }

        .appointments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .appointments-header h2 {
            margin: 10px 0 0;
            font-size: clamp(2rem, 2vw, 3rem);
            letter-spacing: -0.05em;
            color: var(--ink);
        }

        .mini-card {
            display: grid;
            grid-template-columns: 180px 1fr auto;
            gap: 20px;
            align-items: center;
            background: rgba(255,255,255,0.8);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px 18px;
            color: var(--ink);
        }

        .mini-card .date {
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.04em;
        }

        .mini-card .time {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
        }

        .mini-card h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .mini-card p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .status-pill {
            display: inline-block;
            background: rgba(136, 115, 109, 0.12);
            color: var(--primary);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 980px) {
            .hero { grid-template-columns: 1fr; }
            .promo-visual { margin: 0 auto; }
        }

        @media (max-width: 760px) {
            .topbar { padding: 16px 22px; }
            .nav { gap: 10px; font-size: 12px; }
            .page { padding: 32px 22px 30px; }
            .hero { min-height: auto; }
            .features { grid-template-columns: 1fr; }
            .mini-card { grid-template-columns: 1fr; }
            .appointments-header { display: block; }
        }
    </style>
</head>

<body>

    <header class="topbar">
        <a href="{{ route('home') }}" class="brand" aria-label="Triple-M3 home">
            <img class="brand-mark" src="{{ asset('images/triplem3.jpg') }}" alt="">
            <span class="brand-copy">TRIPLE-M3<small>AIR-CONDITIONING SERVICES</small></span>
        </a>

        <nav class="nav" aria-label="Main navigation">
            <a href="#services">Services</a>
            <a href="{{ route('admin.login') }}" class="btn-primary">Admin Login</a>
            @auth
                <a href="{{ route('customer.dashboard') }}">My appointments</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" style="border:0;background:none;color:#242323;font:inherit;font-weight:700;cursor:pointer;">Sign out</button></form>
            @else
                <a href="{{ route('login') }}" class="btn-primary">Customer Sign In</a>
            @endauth
        </nav>
    </header>

    <main class="page">
        <section class="hero">
            <div>
                <span class="eyebrow">Fast, reliable HVAC care</span>
                <h1>Cooler spaces.<span class="accent">Better service.</span></h1>
                <p class="lead">Professional air-conditioning services for homes, businesses, and facilities. From installation and maintenance to repair and inspection, Triple-M3 keeps your systems running efficiently.</p>
                <div class="cta-row">
                    <a href="{{ route('appointments.create') }}" class="button primary">Book an Appointment →</a>
                    <a href="#services" class="button secondary">Explore Services</a>
                </div>
            </div>

            <div class="promo-visual" aria-hidden="true">
                <div class="promo-ring"></div>
                <div class="snowflake">❄</div>
            </div>
        </section>

        <section class="features" id="services">
            <article class="feature-card">
                <div class="feature-icon">✓</div>
                <h3>Professional Service</h3>
                <p>Reliable technicians and quality workmanship.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">◎</div>
                <h3>Complete HVAC Care</h3>
                <p>Installation, repair, maintenance, and inspection.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon">/</div>
                <h3>Easy Appointment</h3>
                <p>Request a service appointment online anytime.</p>
            </article>
        </section>

        @auth
            <section class="appointments" id="appointments">
                <div class="appointments-header">
                    <div>
                        <span class="eyebrow" style="padding:0; background:none; letter-spacing:0.12em;">Your service requests</span>
                        <h2>My appointments</h2>
                    </div>
                    <a href="{{ route('customer.dashboard') }}" class="button secondary">Open full dashboard</a>
                </div>

                @if($appointments->isEmpty())
                    <div class="mini-card">
                        <div>
                            <div class="date">No appointments yet</div>
                            <div class="time">Your booked service will appear here.</div>
                        </div>
                        <div>
                            <h3>Book your first appointment</h3>
                            <p>Your upcoming service request details will show once scheduled.</p>
                        </div>
                        <a href="{{ route('appointments.create') }}" class="button primary">Book now</a>
                    </div>
                @else
                    @foreach($appointments as $appointment)
                        <div class="mini-card" style="margin-top: 12px;">
                            <div>
                                <div class="date">{{ $appointment->scheduled_at->format('D, M j, Y') }}</div>
                                <div class="time">{{ $appointment->scheduled_at->format('g:i A') }} - {{ $appointment->scheduled_at->copy()->addMinutes(\App\Models\ServiceJob::SLOT_DURATION_MINUTES)->format('g:i A') }}</div>
                            </div>
                            <div>
                                <h3>{{ $appointment->service_type }}</h3>
                                <p>Request {{ $appointment->job_number }} · {{ $appointment->notes ?: 'No additional notes provided.' }}</p>
                            </div>
                            <span class="status-pill">{{ $appointment->status }}</span>
                        </div>
                    @endforeach
                @endif
            </section>
        @endauth
    </main>
</body>
</html>
```
