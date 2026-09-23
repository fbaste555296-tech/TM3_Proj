<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Triple-M3 Operations' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <style>.brand{font-family:'Plus Jakarta Sans',sans-serif}.card{border-radius:18px}.label{font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.field{width:100%;border-radius:10px;padding:.7rem .8rem;font-size:.9rem}.nav-active{border-radius:10px}</style>
</head>
<body class="tm-layout">
<div class="min-h-screen md:flex">
    <aside class="tm-sidebar w-full md:w-64 p-5 md:fixed md:inset-y-0">
        <a href="{{ route('dashboard') }}" class="mb-10 flex items-center gap-3">
            <div class="tm-mark">M3</div><div><div class="brand text-sm">TRIPLE-M3</div><div class="text-[10px] tracking-widest">SERVICE OPERATIONS</div></div>
        </a>
        <nav class="space-y-1 text-sm">@foreach(['dashboard'=>'Overview','jobs.index'=>'Service Jobs','customers.index'=>'Customers','inventory.index'=>'Inventory','invoices.index'=>'Billing','reports.index'=>'Reports'] as $route=>$name)<a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'nav-active' : '' }} block px-3 py-2.5">{{ $name }}</a>@endforeach</nav>
        <div class="mt-10 text-xs md:absolute md:bottom-5"><p class="mb-2 opacity-70">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p><form method="POST" action="{{ route('logout') }}">@csrf<button class="font-bold">Sign out →</button></form></div>
    </aside>
    <main class="min-h-screen md:ml-64 flex-1"><header class="flex items-center justify-between border-b px-6 py-5"><div><p class="tm-eyebrow">TRIPLE-M3 CONTROL CENTER</p><h1 class="brand mt-1 text-xl font-extrabold">{{ $heading ?? 'Operations overview' }}</h1></div><p class="hidden text-right text-xs font-semibold sm:block">{{ now()->format('l, M j') }}<br><span class="opacity-60">Keep every job moving.</span></p></header><section class="p-5 md:p-8">@if(session('success'))<div class="mb-5 rounded-xl border p-3 text-sm" style="border-color:var(--teal);color:var(--teal)">{{ session('success') }}</div>@endif @if($errors->any())<div class="mb-5 rounded-xl border p-3 text-sm" style="border-color:var(--teal);color:var(--teal)">{{ $errors->first() }}</div>@endif {{ $slot }}</section></main>
</div>
</body></html>
