<x-layouts.app :heading="'Management reports'">
    @php
        $bookingTotal = $jobs->count();
        $confirmed = $jobs->whereNotIn('status', ['Pending', 'Cancelled'])->count();
        $dispatched = $jobs->whereIn('status', ['Dispatched', 'In Progress', 'Completed'])->count();
        $maxServiceJobs = max(1, (int) $byService->max('total'));
    @endphp

    <style>
        .report-kpi { min-height: 100px; }
        .report-kpi-value { margin-top: 10px; font-size: clamp(22px, 2.3vw, 30px); font-weight: 800; color: #242323; }
        .report-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 14px; }
        .report-span-3 { grid-column: span 3; }
        .report-span-4 { grid-column: span 4; }
        .report-span-6 { grid-column: span 6; }
        .report-span-12 { grid-column: span 12; }
        .report-panel-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #242323; }
        .report-funnel-row { display: grid; grid-template-columns: 90px minmax(0, 1fr) 32px; align-items: center; gap: 8px; margin: 7px 0; font-size: 11px; }
        .report-funnel-bar { height: 21px; display: grid; place-items: center; min-width: 36px; margin-inline: auto; color: white; background: #88736d; font-weight: 700; clip-path: polygon(0 0, 100% 0, 87% 100%, 13% 100%); }
        .report-rate-card { background: #eeecea; }
        .report-category-track { height: 8px; border-radius: 999px; background: #dedbd8; overflow: hidden; }
        .report-category-fill { height: 8px; border-radius: 999px; background: #88736d; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .report-table th, .report-table td { padding: 9px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
        .report-table th { font-size: 10px; text-transform: uppercase; color: #475569; }
        .report-print-header { display: none; }
        .tm-layout .commission-status { display: inline-flex; border-radius: 999px; padding: 4px 8px; font-size: 10px; font-weight: 700; }
        .tm-layout .commission-status-pending { background-color: #fef3c7 !important; color: #92400e !important; }
        .tm-layout .commission-status-paid { background-color: #d1fae5 !important; color: #065f46 !important; }
        .commission-editor { width: min(100% - 32px, 440px); max-height: 90vh; overflow-y: auto; border: 0; border-radius: 16px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
        .commission-editor::backdrop { background: rgba(15, 23, 42, .5); }
        @media (max-width: 900px) {
            .report-span-3 { grid-column: span 6; }
            .report-span-4 { grid-column: span 6; }
            .report-span-6 { grid-column: span 12; }
        }
        @media (max-width: 560px) {
            .report-span-3 { grid-column: span 12; }
            .report-span-4 { grid-column: span 12; }
            .report-controls { grid-template-columns: 1fr 1fr; }
        }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            html, body { width: 100% !important; min-width: 0 !important; }
            body { background: #fff !important; }
            .app-sidebar, .app-header, .report-export { display: none !important; }
            .app-main { width: 100% !important; margin: 0 !important; }
            .app-content { width: 100% !important; padding: 0 !important; }
            .report-print-header { display: block; margin-bottom: 10px; }
            .report-print-header h1 { font-size: 18px !important; }
            .report-grid { grid-template-columns: repeat(12, minmax(0, 1fr)) !important; gap: 6px !important; }
            .report-span-3 { grid-column: span 3 !important; }
            .report-span-4 { grid-column: span 4 !important; }
            .report-span-6 { grid-column: span 6 !important; }
            .report-span-12 { grid-column: span 12 !important; }
            .report-panel, .report-kpi { min-width: 0 !important; padding: 8px !important; break-inside: avoid; box-shadow: none !important; }
            .report-kpi { min-height: 0 !important; }
            .report-kpi-value { margin-top: 4px !important; font-size: 18px !important; }
            .report-panel-title { font-size: 8px !important; }
            .report-table { font-size: 8px !important; }
            .report-table th, .report-table td { padding: 4px !important; font-size: 8px !important; white-space: normal !important; overflow-wrap: anywhere; }
            .report-funnel-row { grid-template-columns: 70px minmax(0, 1fr) 20px; gap: 4px; margin: 4px 0; font-size: 8px; }
            .report-funnel-bar { height: 14px; font-size: 8px; }
            .overflow-x-auto { overflow: visible !important; }
            .report-actions { display: none !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    <header class="report-print-header">
        <p class="label">TRIPLE-M3 · MANAGEMENT REPORT</p>
        <h1 style="margin:8px 0;font-size:24px;font-weight:800;color:#0f172a;">Management reports</h1>
        <p style="margin:0;color:#64748b;font-size:12px;">{{ $range === 'all' ? 'All dates' : \Carbon\Carbon::parse($startDate)->format('M j, Y').' - '.\Carbon\Carbon::parse($endDate)->format('M j, Y') }}</p>
    </header>

    <p class="mb-3 text-[10px] font-bold uppercase tracking-[.14em] text-slate-500">
        Management summary · {{ $range === 'all' ? 'All dates' : \Carbon\Carbon::parse($startDate)->format('M j, Y').' - '.\Carbon\Carbon::parse($endDate)->format('M j, Y') }}
    </p>
    <div class="report-export mb-3 flex justify-end gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">Export CSV</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">Export PDF</button>
    </div>

    <div class="report-grid">
        <section class="card report-kpi report-span-4 p-4">
            <p class="report-panel-title">Total revenue</p>
            <p class="report-kpi-value">₱{{ number_format($totalRevenue, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Completed job subtotal</p>
        </section>
        <section class="card report-kpi report-span-4 p-4">
            <p class="report-panel-title">Total completed jobs</p>
            <p class="report-kpi-value">{{ number_format($completed) }}</p>
        </section>
        <section class="card report-kpi report-span-4 p-4">
            <p class="report-panel-title">Average job value</p>
            <p class="report-kpi-value">₱{{ number_format($averageJobValue, 2) }}</p>
        </section>

        <section class="card report-panel report-span-6 p-4">
            <h2 class="report-panel-title">Service category breakdown</h2>
            <div class="mt-4">
                    @forelse($byService as $service)
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between gap-2 text-[11px]">
                                <span class="truncate">{{ $service->service_type }}</span><span>{{ $service->total }} jobs</span>
                            </div>
                            <div class="report-category-track">
                                <div class="report-category-fill" style="width:{{ round($service->total / $maxServiceJobs * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No completed service jobs in this range.</p>
                    @endforelse
            </div>
        </section>

        <section class="card report-panel report-span-6 p-4">
            <h2 class="report-panel-title">Booking conversion funnel</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-[1fr_125px] sm:items-center">
                <div>
                    @foreach([
                        ['Total bookings', $bookingTotal],
                        ['Confirmed', $confirmed],
                        ['Dispatched', $dispatched],
                        ['Completed', $completed],
                    ] as [$label, $count])
                        <div class="report-funnel-row">
                            <span class="text-right">{{ $label }}</span>
                            <span class="report-funnel-bar" style="width:{{ $bookingTotal ? max(36, $count / $bookingTotal * 100) : 36 }}%">{{ $count }}</span>
                            <span></span>
                        </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-1">
                    <div class="report-rate-card rounded-lg p-3">
                        <p class="text-[10px] text-slate-500">No-show rate</p>
                        <p class="text-lg font-bold">Not tracked</p>
                    </div>
                    <div class="report-rate-card rounded-lg p-3">
                        <p class="text-[10px] text-slate-500">Cancellation rate</p>
                        <p class="text-lg font-bold">{{ number_format($cancelledRate, 1) }}%</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card report-panel report-span-6 p-4">
            <h2 class="report-panel-title">Technician performance leaderboard</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="report-table">
                    <thead><tr><th>#</th><th>Technician</th><th>Completed jobs</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($technicianPerformance as $index => $technician)
                            <tr>
                                <td class="font-bold">{{ $index + 1 }}</td>
                                <td>{{ $technician->name }}</td>
                                <td>{{ $technician->jobs }}</td>
                                <td>₱{{ number_format($technician->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500">No technician completions in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card report-panel report-span-6 p-4">
            <h2 class="report-panel-title">Expanded commission &amp; payout ledger</h2>
            <p class="mt-1 text-xs text-slate-500">Commission entries for jobs in the selected report range.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="report-table">
                    <thead><tr><th>Date</th><th>Technician</th><th>Job ID</th><th>Type</th><th>Subtotal</th><th>Commission</th><th>Status</th><th class="report-actions">Actions</th></tr></thead>
                    <tbody>
                        @forelse($commissions as $commission)
                            <tr>
                                <td>{{ $commission->job?->scheduled_at?->format('M j') ?? '—' }}</td>
                                <td>{{ $commission->technician?->name ?? 'Unassigned' }}</td>
                                <td>{{ $commission->job?->job_number ?? '—' }}</td>
                                <td>{{ $commission->job?->service_type ?? '—' }}</td>
                                <td>₱{{ number_format((float) ($commission->job?->invoice?->subtotal ?? 0), 2) }}</td>
                                <td>₱{{ number_format($commission->amount, 2) }}</td>
                                <td><span class="commission-status {{ $commission->status === 'Paid' ? 'commission-status-paid' : 'commission-status-pending' }}">{{ $commission->status }}</span></td>
                                <td class="report-actions">
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="document.getElementById('commissionEditor{{ $commission->id }}').showModal()" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200">Edit</button>
                                        <form method="POST" action="{{ route('commissions.delete', ['commission' => $commission, ...request()->query()]) }}" onsubmit="return confirm('Delete this commission entry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Delete</button>
                                        </form>
                                    </div>
                                    <dialog id="commissionEditor{{ $commission->id }}" class="commission-editor">
                                        <form method="POST" action="{{ route('commissions.update', ['commission' => $commission, ...request()->query()]) }}" class="grid gap-4">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <h3 class="text-lg font-extrabold text-slate-900">Edit commission entry</h3>
                                                <p class="mt-1 text-sm text-slate-500">{{ $commission->job?->job_number ?? 'Job' }} · {{ $commission->technician?->name ?? 'Unassigned' }}</p>
                                            </div>
                                            <label class="grid gap-1 text-sm font-semibold text-slate-700">Commission rate (%)
                                                <input class="field" type="number" name="rate" min="0" max="100" step="0.01" value="{{ $commission->rate }}" required>
                                            </label>
                                            <label class="grid gap-1 text-sm font-semibold text-slate-700">Commission amount (₱)
                                                <input class="field" type="number" name="amount" min="0" step="0.01" value="{{ $commission->amount }}" required>
                                            </label>
                                            <label class="grid gap-1 text-sm font-semibold text-slate-700">Payout status
                                                <select class="field" name="status" required>
                                                    <option value="Pending" @selected($commission->status === 'Pending')>Pending</option>
                                                    <option value="Paid" @selected($commission->status === 'Paid')>Paid</option>
                                                </select>
                                            </label>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button>
                                                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Save changes</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-slate-500">No commission entries in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</x-layouts.app>
