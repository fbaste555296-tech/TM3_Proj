<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My appointments | Triple-M3</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <style>
        body { padding: 24px; }
        .dashboard { width: min(100%, 980px); margin: 24px auto; }
        .dashboard-head { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:28px; }
        .dashboard-head h1 { margin:8px 0 0; font-size:clamp(28px,5vw,46px); letter-spacing:-.05em; }
        .appointment-list { display:grid; gap:14px; }
        .appointment { display:grid; grid-template-columns:190px 1fr auto; gap:22px; align-items:center; padding:22px; }
        .appointment-date { font-size:17px; font-weight:800; line-height:1.4; }
        .appointment-time { margin-top:5px; font-size:13px; opacity:.6; }
        .appointment h2 { margin:0 0 7px; font-size:16px; }
        .appointment p { margin:0; font-size:13px; line-height:1.6; opacity:.68; }
        .status { display:inline-block; padding:7px 10px; border-radius:999px; background:rgba(26,145,136,.1); color:var(--teal); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
        .empty { padding:42px 24px; text-align:center; }
        .empty p { max-width:420px; margin:10px auto 24px; line-height:1.7; opacity:.68; }
        @media(max-width:680px) { .dashboard-head { display:block; } .dashboard-head .tm-button { margin-top:20px; } .appointment { grid-template-columns:1fr; gap:14px; } .status { justify-self:start; } }
    </style>
</head>
<body>
    <main class="dashboard">
        <a href="{{ route('home') }}" style="font-size:13px;font-weight:800;color:var(--teal)">← TRIPLE-M3</a>
        <div class="dashboard-head">
            <div>
                <p class="tm-eyebrow" style="margin-top:30px">CUSTOMER DASHBOARD</p>
                <h1>My appointments</h1>
                <p style="margin-top:12px;opacity:.68">Keep track of every service request and its current status.</p>
            </div>
            <a href="{{ route('appointments.create') }}" class="tm-button">Book an appointment</a>
        </div>

        @if($appointments->isEmpty())
            <section class="tm-card empty">
                <div class="tm-mark" style="margin:auto">M3</div>
                <h2 style="margin:20px 0 0;font-size:21px">No appointments yet</h2>
                <p>When you book a service, your date, time, request details, and confirmation status will appear here.</p>
                <a href="{{ route('appointments.create') }}" class="tm-button">Book your first appointment</a>
            </section>
        @else
            <section class="appointment-list" aria-label="Your appointments">
                @foreach($appointments as $appointment)
                    <article class="tm-card appointment">
                        <div>
                            <div class="appointment-date">{{ $appointment->scheduled_at->format('D, M j, Y') }}</div>
                            <div class="appointment-time">{{ $appointment->scheduled_at->format('g:i A') }} · {{ $appointment->duration_minutes }} minutes</div>
                        </div>
                        <div>
                            <h2>{{ $appointment->service_type }}</h2>
                            <p>Request {{ $appointment->job_number }} · {{ $appointment->notes ?: 'No additional notes provided.' }}</p>
                        </div>
                        <span class="status">{{ $appointment->status }}</span>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
</body>
</html>
