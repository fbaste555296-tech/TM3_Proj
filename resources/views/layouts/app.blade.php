<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Triple-M3 Operations' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <link href="{{ asset('css/triple-m3-theme.css') }}" rel="stylesheet">
    <style>
        :root {
            --teal: #88736d;
            --teal-dark: #242323;
            --sidebar-w: 256px;
            --page-bg: #f6f5f3;
            --line: #dedbd8;
        }

        * { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--page-bg); color: #1e293b; margin: 0; }

        .brand { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card { border-radius: 18px; background: #fff; border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .label { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        .field { width: 100%; border-radius: 10px; padding: .7rem .8rem; font-size: .9rem; background: #fff; color: #1e293b; border: 1px solid #d5dfdd; outline: none; transition: border-color .15s, box-shadow .15s; }
        .field:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(136, 115, 109, .16); }

        /* Sidebar */
        .app-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 50; display: flex; flex-direction: column; width: var(--sidebar-w); padding: 20px; background: var(--teal-dark); color: #fff; transform: translateX(-100%); transition: transform .25s ease; }
        .app-sidebar.open { transform: translateX(0); }
        .app-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; color: #fff; text-decoration: none; }
        .app-logo .mark { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: rgba(255, 255, 255, .16); font-weight: 800; font-size: 14px; }
        .app-logo small { display: block; font-size: 10px; letter-spacing: .16em; opacity: .7; }

        .app-nav { display: grid; gap: 4px; }
        .app-nav a { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; color: rgba(255, 255, 255, .75); font-size: 14px; font-weight: 600; text-decoration: none; transition: background .15s, color .15s; }
        .app-nav a:hover { background: rgba(255, 255, 255, .08); color: #fff; }
        .app-nav a.active { background: rgba(255, 255, 255, .16); color: #fff; box-shadow: inset 3px 0 0 #fff; }
        .app-nav svg { flex-shrink: 0; opacity: .9; }

        .app-user { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, .15); font-size: 12px; }
        .app-user p { margin: 0 0 10px; opacity: .8; }
        .app-user button { width: 100%; padding: 9px 12px; border: 0; border-radius: 10px; background: rgba(255, 255, 255, .12); color: #fff; font: inherit; font-weight: 700; text-align: left; cursor: pointer; transition: background .15s; }
        .app-user button:hover { background: rgba(255, 255, 255, .22); }

        .app-overlay { display: none; position: fixed; inset: 0; z-index: 40; background: rgba(15, 23, 42, .5); }
        .app-overlay.open { display: block; }

        /* Main area */
        .app-main { min-height: 100vh; }
        .app-header { position: sticky; top: 0; z-index: 30; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .app-header .eyebrow { margin: 0; font-size: 10px; font-weight: 800; letter-spacing: .14em; color: var(--teal); }
        .app-header h1 { margin: 2px 0 0; font-size: 20px; font-weight: 800; letter-spacing: -.02em; }
        .menu-btn { display: inline-grid; place-items: center; width: 40px; height: 40px; border: 1px solid var(--line); border-radius: 10px; background: #fff; color: #1e293b; cursor: pointer; }
        .global-filter-bar { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)) auto; align-items: end; gap: 10px; width: 100%; }
        .global-filter-field { display: grid; min-width: 0; gap: 5px; }
        .global-filter-field label { font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #475569; }
        .global-filter-field input, .global-filter-field select { width: 100%; min-width: 0; border: 1px solid #cbd5e1; border-radius: 9px; padding: 9px 10px; background: #fff; color: #14213d; font: inherit; font-size: 12px; }
        .global-filter-actions { display: flex; gap: 8px; }
        .global-filter-button { min-height: 38px; border: 0; border-radius: 9px; background: var(--teal); color: #fff; padding: 9px 14px; font-size: 12px; font-weight: 800; cursor: pointer; white-space: nowrap; }
        .global-filter-clear { display: inline-flex; align-items: center; border-radius: 9px; background: #e2e8f0; color: #14213d; padding: 9px 12px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .global-filter-custom[hidden] { display: none !important; }

        .app-content { padding: 20px; }

        .flash { margin-bottom: 20px; padding: 12px 14px; border-radius: 12px; font-size: 14px; font-weight: 600; }
        .flash.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .flash.error { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }

        @media (max-width: 1150px) {
            .global-filter-bar { grid-template-columns: repeat(4, minmax(120px, 1fr)); }
            .global-filter-actions { grid-column: 1 / -1; justify-content: flex-end; }
        }

        @media (max-width: 600px) {
            .global-filter-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .global-filter-actions { grid-column: 1 / -1; }
        }

        @media (min-width: 768px) {
            .app-sidebar { transform: none; }
            .app-overlay, .app-overlay.open { display: none; }
            .app-main { margin-left: var(--sidebar-w); }
            .menu-btn { display: none; }
            .app-header { padding: 18px 32px; }
            .app-content { padding: 32px; }
        }
    </style>
</head>

<body class="tm-layout">

    @php
        $nav = [
            'dashboard'       => ['Overview',     'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
            'jobs.index'      => ['Service Jobs', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6M9 13l2 2 4-4'],
            'customers.index' => ['Customers',     'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z'],
            'inventory.index' => ['Inventory',     'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            'invoices.index'  => ['Billing',       'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
            'reports.index'   => ['Reports',       'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ];
    @endphp

    <div id="appOverlay" class="app-overlay"></div>

    <aside id="appSidebar" class="app-sidebar tm-sidebar">
        <a href="{{ route('dashboard') }}" class="app-logo">
            <img class="mark tm-mark" src="{{ asset('images/triplem3.jpg') }}" alt="Triple-M3 logo">
            <div>
                <div class="brand" style="font-size:14px;font-weight:800;">TRIPLE-M3</div>
                <small>SERVICE OPERATIONS</small>
            </div>
        </a>

        <nav class="app-nav">
            @foreach($nav as $route => [$name, $icon])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active nav-active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $icon }}" />
                    </svg>
                    {{ $name }}
                </a>
            @endforeach
        </nav>

        <div class="app-user">
            <p>{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out →</button>
            </form>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="button" id="menuBtn" class="menu-btn" aria-label="Open menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div>
                    <p class="eyebrow tm-eyebrow">TRIPLE-M3 CONTROL CENTER</p>
                    <h1 class="brand">{{ $heading ?? 'Operations overview' }}</h1>
                </div>
            </div>

            <p class="hidden text-right text-xs font-semibold sm:block" style="margin:0;">
                {{ now()->format('l, M j') }}<br>
                <span style="opacity:.6;">Keep every job moving.</span>
            </p>
            @unless(request()->routeIs('customers.*', 'inventory.*', 'invoices.*'))
            <form method="GET" action="{{ request()->url() }}" class="global-filter-bar">
                @foreach(request()->query() as $key => $value)
                    @if(!in_array($key, ['range', 'start_date', 'end_date', 'technician_id', 'location', 'status', 'page', 'export'], true) && is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="global-filter-field">
                    <label for="globalFilterRange">Date range</label>
                    <select id="globalFilterRange" name="range">
                        <option value="all" @selected($globalFilterRange === 'all')>All Dates</option>
                        <option value="today" @selected($globalFilterRange === 'today')>Today</option>
                        <option value="week" @selected($globalFilterRange === 'week')>This Week</option>
                        <option value="month" @selected($globalFilterRange === 'month')>This Month</option>
                        <option value="custom" @selected($globalFilterRange === 'custom')>Custom Range</option>
                    </select>
                </div>
                <div class="global-filter-field global-filter-custom" @if($globalFilterRange !== 'custom') hidden @endif>
                    <label for="globalFilterStart">Start date</label>
                    <input id="globalFilterStart" type="date" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="global-filter-field global-filter-custom" @if($globalFilterRange !== 'custom') hidden @endif>
                    <label for="globalFilterEnd">End date</label>
                    <input id="globalFilterEnd" type="date" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="global-filter-field">
                    <label for="globalFilterTechnician">Technician</label>
                    <select id="globalFilterTechnician" name="technician_id">
                        <option value="">All Technicians</option>
                        @foreach($globalFilterTechnicians as $technician)
                            <option value="{{ $technician->id }}" @selected(request('technician_id') == $technician->id)>{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="global-filter-field">
                    <label for="globalFilterLocation">Branch / Location</label>
                    <select id="globalFilterLocation" name="location">
                        <option value="">All Locations</option>
                        @foreach($globalFilterLocations as $location)
                            <option value="{{ $location }}" @selected(request('location') === $location)>{{ \Illuminate\Support\Str::limit($location, 36) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="global-filter-field">
                    <label for="globalFilterStatus">Job status</label>
                    <select id="globalFilterStatus" name="status">
                        <option value="">All Statuses</option>
                        @foreach($globalFilterStatuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="global-filter-actions">
                    <button type="submit" class="global-filter-button">Apply</button>
                    @if(request()->hasAny(['range', 'start_date', 'end_date', 'technician_id', 'location', 'status']))
                        <a href="{{ request()->url() }}" class="global-filter-clear">Clear</a>
                    @endif
                </div>
            </form>
            @endunless
        </header>

        <section class="app-content">
            @if(session('success'))
                <div class="flash success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </section>
    </main>

    <script>
        (function () {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.getElementById('appOverlay');
            const btn = document.getElementById('menuBtn');
            const filterRange = document.getElementById('globalFilterRange');
            const customDateFields = document.querySelectorAll('.global-filter-custom');
            if (filterRange) {
                filterRange.addEventListener('change', function () {
                    customDateFields.forEach(field => field.hidden = this.value !== 'custom');
                });
                const filterForm = filterRange.form;
                filterForm.addEventListener('submit', function (event) {
                    if (filterRange.value === 'custom') {
                        const startDate = document.getElementById('globalFilterStart');
                        const endDate = document.getElementById('globalFilterEnd');
                        if (!startDate.value || !endDate.value) {
                            event.preventDefault();
                            (startDate.value ? endDate : startDate).focus();
                        }
                    }
                });
            }

            function toggle(open) {
                sidebar.classList.toggle('open', open);
                overlay.classList.toggle('open', open);
            }

            btn.addEventListener('click', () => toggle(!sidebar.classList.contains('open')));
            overlay.addEventListener('click', () => toggle(false));
            document.addEventListener('keydown', e => { if (e.key === 'Escape') toggle(false); });
        })();
    </script>
</body>

</html>