<x-layouts.app :heading="'Service jobs & dispatch'">

    <style>
        .job-row { background: #fff; color: #1e293b; border: 1px solid #e2e8f0; }
        .job-row:hover { border-color: #cbd5e1; }
        .job-row.is-active { background: #f8fafc; border-color: #94a3b8; }
        .job-status { background: none; color: #1e293b; font-weight: 600; font-size: 12px; padding: 0; white-space: nowrap; }
        #jobEditorPhone { background: #f8fafc; color: #1e293b; cursor: not-allowed; }
        .job-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .45); }
        .job-modal.open { display: flex; }
        #jobEditorModal { align-items: flex-start; }
        .job-modal-box { position: relative; width: min(100%, 560px); max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 16px; background: #fff; color: #1e293b; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
        .job-modal-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border: 0; border-radius: 8px; background: #f1f5f9; color: #1e293b; font-size: 18px; line-height: 1; cursor: pointer; }
        .job-modal-close:hover { background: #e2e8f0; }
        .job-detail { display: none; }
        .job-detail.show { display: block; }
        .job-action-button { display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; padding: 0.625rem 1rem; font-size: 0.75rem; font-weight: 700; }

        .walkin-card {
            width: min(100%, 560px);
            max-height: calc(100vh - 32px);
            max-height: calc(100dvh - 32px);
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            margin-block: auto;
            border-radius: 20px;
            background: #fff;
            border: 1px solid #dfe7ee;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
            padding: 28px 24px;
            color: #1f2937;
        }
        .walkin-header { margin-bottom: 20px; }
        .walkin-eyebrow {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.18em;
            color: #0f766e;
            text-transform: uppercase;
        }
        .walkin-title {
            margin: 0 0 10px;
            font-size: clamp(2rem, 2vw, 2.4rem);
            font-weight: 800;
            color: #1f2937;
        }
        .walkin-subtitle {
            margin: 0;
            font-size: 14px;
            color: #64748b;
        }
        .walkin-form { display: grid; gap: 18px; }
        .walkin-field { display: grid; gap: 8px; }
        .walkin-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #475569;
        }
        .walkin-input,
        .walkin-select,
        .walkin-textarea {
            width: 100%;
            border: 1px solid #d7e0ea;
            border-radius: 12px;
            background: #f8fafc;
            color: #1f2937;
            padding: 12px 14px;
            font-size: 16px;
            font-family: inherit;
        }
        .walkin-input[readonly] { background: #f1f5f9; color: #334155; cursor: not-allowed; }
        .walkin-textarea { min-height: 110px; resize: vertical; }
        .walkin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .walkin-actions {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-top: 8px;
        }
        .walkin-button {
            border: none;
            border-radius: 12px;
            padding: 0.9rem 1.3rem;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        .walkin-button:hover { opacity: 0.96; }
        .walkin-button-primary { background: #0f766e; color: white; }
        .walkin-button-secondary { background: #f1f5f9; color: #1f2937; }
        .walkin-button-danger { background: #fee2e2; color: #b91c1c; }
        @media (max-width: 640px) {
            .walkin-grid { grid-template-columns: 1fr; }
            .walkin-actions { flex-direction: column-reverse; }
            .walkin-actions > * { width: 100%; }
        }
    </style>

    <div class="space-y-6">
        <div class="card p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="brand">Dispatch board</h2>
                    <p class="text-sm text-slate-500">Choose a customer to open the job editor, or click a job for quick details.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-[220px]">
                        <label for="jobCustomerPicker" class="label">Customer</label>
                        <select id="jobCustomerPicker" class="field mt-1">
                            <option value="">Select customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="newJobButton" class="job-action-button bg-emerald-600 text-white hover:bg-emerald-700">
                        New service job
                    </button>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="brand">Technician roster</h2>
                    <p class="text-sm text-slate-500">Assign technicians to each service type as needed.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    {{ $technicians->count() }} total
                </span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @foreach($serviceTypes as $serviceType)
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-bold text-slate-700">{{ $serviceType }}</h3>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                                {{ count($techniciansByService[$serviceType] ?? []) }}
                            </span>
                        </div>

                        @forelse($techniciansByService[$serviceType] ?? [] as $tech)
                            <div class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">{{ $tech->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $tech->phone ?: 'No phone number' }}</p>
                                    @if($busyJob = $busyJobsByTechnician->get($tech->id)?->first())
                                        <p class="mt-1 text-xs font-semibold text-amber-700">
                                            Busy — doing another job ({{ $busyJob->job_number }} · {{ $busyJob->status }})
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs font-semibold text-emerald-700">Available</p>
                                    @endif
                                </div>
                                <form action="{{ route('technicians.delete', $tech) }}" method="POST" onsubmit="return confirm('Delete this technician?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-rose-700 hover:bg-rose-100">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No technicians assigned.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <form action="{{ route('technicians.store') }}" method="POST" class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                @csrf
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700">Add technician</h3>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="label">Name</label>
                        <input name="name" class="field mt-1" value="{{ old('name') }}" placeholder="e.g. Miguel Santos" required>
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input name="phone" class="field mt-1" value="{{ old('phone') }}" placeholder="e.g. 09123456789">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Email</label>
                        <input type="email" name="email" class="field mt-1" value="{{ old('email') }}" placeholder="tech@example.com" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="label">Service types</label>
                    <div class="mt-2 grid gap-2 md:grid-cols-2">
                        @foreach($serviceTypes as $serviceType)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="service_types[]" value="{{ $serviceType }}" {{ in_array($serviceType, old('service_types', []), true) ? 'checked' : '' }}>
                                <span>{{ $serviceType }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="mt-4 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                    Add technician
                </button>
            </form>
        </div>

        <div class="card p-6">
            <form id="serviceJobSearchForm" action="{{ route('jobs.index') }}" method="GET" class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end">
                @foreach(request()->only(['range', 'start_date', 'end_date', 'technician_id']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="min-w-0 flex-1">
                    <label for="serviceJobSearch" class="label">Search service jobs</label>
                    <input id="serviceJobSearch"
                           name="search"
                           type="search"
                           class="field mt-1"
                           value="{{ request('search') }}"
                           placeholder="Search by customer or technician..."
                           autocomplete="off">
                </div>
                <button type="submit" class="job-action-button bg-emerald-600 text-white hover:bg-emerald-700">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="job-action-button bg-slate-100 text-slate-700 hover:bg-slate-200">Clear</a>
                @endif
            </form>
            <div class="space-y-3">
                @forelse($jobs as $job)
                    <div class="job-row cursor-pointer rounded-xl p-4 transition"
                         data-job-row="{{ $job->id }}"
                         data-job-id="{{ $job->id }}"
                         data-customer-id="{{ $job->customer_id }}"
                         data-customer-phone="{{ $job->customer?->phone ?? '' }}"
                         data-service-type="{{ $job->service_type }}"
                         data-technician-id="{{ $job->technician_id ?? '' }}"
                         data-scheduled-date="{{ $job->scheduled_at->format('Y-m-d') }}"
                         data-time-slot="{{ $job->scheduled_at->format('H:i') }}"
                         data-priority="{{ $job->priority }}"
                         data-labor-cost="{{ $job->labor_cost }}"
                         data-notes="{{ e($job->notes ?? '') }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold">{{ $job->job_number }} · {{ $job->service_type }}</p>
                                <p class="text-sm font-semibold">{{ $job->customer?->name ?? 'Unknown customer' }}</p>
                                <p class="text-sm text-slate-500">Phone: {{ $job->customer?->phone ?: 'No phone number' }}</p>
                                <p class="text-sm text-slate-500">{{ $job->scheduled_at->format('M d, Y · g:i A') }} - {{ $job->scheduled_at->copy()->addMinutes(\App\Models\ServiceJob::SLOT_DURATION_MINUTES)->format('g:i A') }}</p>
                                <p class="mt-1 text-xs text-slate-500">Tech: {{ $job->technician?->name ?? 'Unassigned' }} · {{ $job->priority }} priority</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="job-status">{{ $job->status }}</span>

                                <form action="{{ route('jobs.status', $job) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status"
                                            aria-label="Change status for {{ $job->job_number }}"
                                            onchange="this.form.submit()"
                                            class="rounded-lg border p-2 text-xs">
                                        @foreach(['Pending', 'Dispatched', 'In Progress', 'Completed', 'Cancelled'] as $state)
                                            <option value="{{ $state }}" @selected($job->status === $state)>{{ $state }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-slate-500">
                        {{ request()->filled('search') ? 'No service jobs match that customer or technician.' : 'No jobs booked yet.' }}
                    </p>
                @endforelse
            </div>

            <div class="mt-4">{{ $jobs->links() }}</div>
        </div>
    </div>

    @if($jobs->count())
        <div id="jobModal" class="job-modal" role="dialog" aria-modal="true" aria-label="Job details">
            <div class="job-modal-box">
                <button type="button" id="jobModalClose" class="job-modal-close" aria-label="Close">×</button>

                <h2 class="brand">Job details</h2>
                <p class="mb-4 text-sm text-slate-500">Schedule, contact, and reported concern.</p>

                @foreach($jobs as $job)
                    <div class="job-detail" data-job-detail="{{ $job->id }}">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <th class="w-36 py-3 text-left text-xs font-bold uppercase text-slate-500">Job no.</th>
                                    <td class="py-3 font-semibold">{{ $job->job_number }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Customer</th>
                                    <td class="py-3">{{ $job->customer?->name ?? 'Unknown customer' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Phone</th>
                                    <td class="py-3">
                                        @if($job->customer?->phone)
                                            <a href="tel:{{ $job->customer->phone }}" class="font-semibold text-emerald-700 hover:underline">{{ $job->customer->phone }}</a>
                                        @else
                                            <span class="text-slate-400">No phone number</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Address</th>
                                    <td class="py-3">{{ $job->address ?? $job->customer?->address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Service</th>
                                    <td class="py-3">{{ $job->service_type }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Date</th>
                                    <td class="py-3">{{ $job->scheduled_at->format('l, M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Time</th>
                                    <td class="py-3">
                                        {{ $job->scheduled_at->format('g:i A') }} - {{ $job->scheduled_at->copy()->addMinutes(\App\Models\ServiceJob::SLOT_DURATION_MINUTES)->format('g:i A') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Technician</th>
                                    <td class="py-3">{{ $job->technician?->name ?? 'Unassigned' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Priority</th>
                                    <td class="py-3">{{ $job->priority }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                                    <td class="py-3">{{ $job->status }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left text-xs font-bold uppercase text-slate-500">Labor cost</th>
                                    <td class="py-3">₱{{ number_format($job->labor_cost ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <th class="py-3 text-left align-top text-xs font-bold uppercase text-slate-500">Concern / notes</th>
                                    <td class="whitespace-pre-line py-3">{{ $job->notes ?: 'No notes provided.' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" class="job-action-button border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                    data-edit-job="{{ $job->id }}">Edit job</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div id="jobEditorModal" class="job-modal" role="dialog" aria-modal="true" aria-label="Service job editor">
        <form id="jobEditorForm" method="POST" class="walkin-card">
            @csrf
            <input type="hidden" name="_method" id="jobEditorMethod" value="POST">

            <button type="button" id="jobEditorClose" class="job-modal-close" aria-label="Close">×</button>

            <div class="walkin-header">
                <p class="walkin-eyebrow">Appointment request</p>
                <h2 id="jobEditorTitle" class="walkin-title">Book your service</h2>
                <p class="walkin-subtitle">Select the customer and update the service details.</p>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="walkin-form">
                <div class="walkin-field">
                    <label for="jobEditorCustomer" class="walkin-label">Customer</label>
                    <select id="jobEditorCustomer" name="customer_id" class="walkin-select" required>
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="walkin-field">
                    <label for="jobEditorPhone" class="walkin-label">Phone number</label>
                    <input id="jobEditorPhone" class="walkin-input" type="text" readonly placeholder="Auto-filled when you select a customer">
                </div>

                <div class="walkin-field">
                    <label for="jobEditorServiceType" class="walkin-label">Service needed</label>
                    <select id="jobEditorServiceType" name="service_type" class="walkin-select" required>
                        @foreach(['Repair & Diagnostics', 'Preventive Maintenance', 'New Installation', 'Inspection'] as $service)
                            <option value="{{ $service }}" @selected(old('service_type') === $service)>{{ $service }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="walkin-grid">
                    <div class="walkin-field">
                        <label for="jobEditorScheduledDate" class="walkin-label">Preferred date</label>
                        <input id="jobEditorScheduledDate" name="scheduled_date" class="walkin-input" type="date" min="{{ now()->toDateString() }}" value="{{ old('scheduled_date') }}" required>
                    </div>
                    <div class="walkin-field">
                        <label for="jobEditorTimeSlot" class="walkin-label">Available time</label>
                        <select id="jobEditorTimeSlot" name="time_slot" class="walkin-select" required>
                            <option value="">Select a time slot</option>
                            @foreach(\App\Models\ServiceJob::TIME_SLOTS as $time => $label)
                                <option value="{{ $time }}" @selected(old('time_slot') === $time)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="walkin-grid">
                    <div class="walkin-field">
                        <label for="jobEditorTechnician" class="walkin-label">Technician</label>
                        <select id="jobEditorTechnician" name="technician_id" class="walkin-select">
                            <option value="">Assign later</option>
                            @foreach($technicians as $tech)
                                @php($busy = $busyJobsByTechnician->has($tech->id))
                                <option value="{{ $tech->id }}"
                                        data-service-types="{{ json_encode($tech->service_types ?? []) }}"
                                        data-busy="{{ $busy ? 'true' : 'false' }}"
                                        @disabled($busy)
                                        @selected(old('technician_id') == $tech->id)>
                                    {{ $tech->name }}{{ $busy ? ' — Busy (doing another job)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="walkin-field">
                        <label for="jobEditorPriority" class="walkin-label">Priority</label>
                        <select id="jobEditorPriority" name="priority" class="walkin-select">
                            @foreach(['Normal', 'High', 'Urgent'] as $level)
                                <option value="{{ $level }}" @selected(old('priority') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="walkin-field">
                    <label for="jobEditorCost" class="walkin-label">Labor cost (₱)</label>
                    <input id="jobEditorCost" name="labor_cost" class="walkin-input" type="number" step=".01" min="0" value="{{ old('labor_cost', 0) }}" required>
                </div>

                <div class="walkin-field">
                    <label for="jobEditorNotes" class="walkin-label">Tell us about the issue</label>
                    <textarea id="jobEditorNotes" name="notes" class="walkin-textarea" placeholder="Example: Aircon is not cooling, unusual noise, leaking water, etc.">{{ old('notes') }}</textarea>
                </div>

                <div class="walkin-actions">
                    <button type="button" id="jobEditorDelete" class="walkin-button walkin-button-danger hidden">Delete</button>
                    <button type="submit" id="jobEditorSubmit" class="walkin-button walkin-button-primary ml-auto">Create service job</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const rows = document.querySelectorAll('[data-job-row]');
            const details = document.querySelectorAll('[data-job-detail]');
            const modal = document.getElementById('jobModal');
            const closeBtn = document.getElementById('jobModalClose');
            const editorModal = document.getElementById('jobEditorModal');
            const editorForm = document.getElementById('jobEditorForm');
            const editorCustomer = document.getElementById('jobEditorCustomer');
            const editorPhone = document.getElementById('jobEditorPhone');
            const editorServiceType = document.getElementById('jobEditorServiceType');
            const editorTechnician = document.getElementById('jobEditorTechnician');
            const editorPriority = document.getElementById('jobEditorPriority');
            const editorCost = document.getElementById('jobEditorCost');
            const editorNotes = document.getElementById('jobEditorNotes');
            const editorTitle = document.getElementById('jobEditorTitle');
            const editorSubmit = document.getElementById('jobEditorSubmit');
            const editorMethod = document.getElementById('jobEditorMethod');
            const editorDelete = document.getElementById('jobEditorDelete');
            const editorScheduledDate = document.getElementById('jobEditorScheduledDate');
            const editorTimeSlot = document.getElementById('jobEditorTimeSlot');
            const customerPicker = document.getElementById('jobCustomerPicker');
            const newJobButton = document.getElementById('newJobButton');

            function openJob(id) {
                if (!modal) return;
                rows.forEach(r => r.classList.toggle('is-active', r.dataset.jobRow === id));
                details.forEach(d => d.classList.toggle('show', d.dataset.jobDetail === id));
                modal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function closeJob() {
                if (!modal) return;
                modal.classList.remove('open');
                document.body.style.overflow = '';
                rows.forEach(r => r.classList.remove('is-active'));
            }

            rows.forEach(row => {
                row.addEventListener('click', e => {
                    if (e.target.closest('form')) return;
                    openJob(row.dataset.jobRow);
                });
            });

            if (closeBtn) closeBtn.addEventListener('click', closeJob);
            if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeJob(); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeJob(); if (editorModal) editorModal.classList.remove('open'); } });

            function fillEditorPhone() {
                const selected = editorCustomer.options[editorCustomer.selectedIndex];
                const phone = selected ? selected.dataset.phone || '' : '';
                editorPhone.value = phone || '';
                editorPhone.placeholder = editorCustomer.value ? 'No phone number saved' : 'Auto-filled when you select a customer';
            }

            function setTechnicianAvailability(currentTechnicianId = '') {
                Array.from(editorTechnician.options).forEach(option => {
                    if (!option.value) return;
                    const serviceTypes = JSON.parse(option.dataset.serviceTypes || '[]');
                    option.hidden = !serviceTypes.includes(editorServiceType.value);
                    option.disabled = option.dataset.busy === 'true' && option.value !== String(currentTechnicianId);
                });
                const selectedOption = editorTechnician.options[editorTechnician.selectedIndex];
                if (selectedOption && selectedOption.hidden) editorTechnician.value = '';
            }

            function openEditorForNew(customerId = '') {
                if (!editorModal || !editorForm) return;

                editorForm.action = '{{ url('/admin/jobs') }}';
                editorMethod.value = 'POST';
                editorTitle.textContent = 'Book your service';
                editorSubmit.textContent = 'Create service job';
                editorDelete.classList.add('hidden');
                editorDelete.dataset.jobId = '';
                editorDelete.onclick = null;

                editorCustomer.value = customerId || '';
                fillEditorPhone();
                editorServiceType.value = '{{ old('service_type', 'Repair & Diagnostics') }}';
                editorTechnician.value = '{{ old('technician_id', '') }}';
                setTechnicianAvailability();
                editorScheduledDate.value = '{{ old('scheduled_date', '') }}';
                editorTimeSlot.value = '{{ old('time_slot', '') }}';
                editorPriority.value = '{{ old('priority', 'Normal') }}';
                editorCost.value = '{{ old('labor_cost', 0) }}';
                editorNotes.value = '{{ old('notes', '') }}';
                editorModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function openEditorForExisting(jobRow) {
                if (!editorModal || !editorForm || !jobRow) return;

                const id = jobRow.dataset.jobId;
                const customerId = jobRow.dataset.customerId || '';
                const technicianId = jobRow.dataset.technicianId || '';
                const scheduledDate = jobRow.dataset.scheduledDate || '';
                const timeSlot = jobRow.dataset.timeSlot || '';
                const serviceType = jobRow.dataset.serviceType || '{{ old('service_type', 'Repair & Diagnostics') }}';

                editorForm.action = '{{ url('/admin/jobs') }}' + '/' + id;
                editorMethod.value = 'PATCH';
                editorTitle.textContent = 'Edit service job';
                editorSubmit.textContent = 'Save changes';
                editorDelete.classList.remove('hidden');
                editorDelete.dataset.jobId = id;
                editorDelete.onclick = function () {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = '{{ url('/admin/jobs') }}' + '/' + id;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';

                    deleteForm.appendChild(csrfInput);
                    deleteForm.appendChild(methodInput);
                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                };

                editorCustomer.value = customerId;
                fillEditorPhone();
                editorServiceType.value = serviceType;
                editorTechnician.value = technicianId;
                setTechnicianAvailability(technicianId);
                editorScheduledDate.value = scheduledDate;
                editorTimeSlot.value = timeSlot;
                editorPriority.value = jobRow.dataset.priority || 'Normal';
                editorCost.value = jobRow.dataset.laborCost || 0;
                editorNotes.value = jobRow.dataset.notes || '';
                editorModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            document.querySelectorAll('[data-edit-job]').forEach(button => {
                button.addEventListener('click', function () {
                    const jobRow = document.querySelector('[data-job-row="' + this.dataset.editJob + '"]');
                    closeJob();
                    openEditorForExisting(jobRow);
                });
            });

            if (customerPicker) {
                customerPicker.addEventListener('change', function () {
                    if (this.value) {
                        openEditorForNew(this.value);
                    }
                });
            }

            if (newJobButton) {
                newJobButton.addEventListener('click', function () {
                    openEditorForNew();
                });
            }

            editorCustomer.addEventListener('change', fillEditorPhone);
            editorServiceType.addEventListener('change', function () {
                setTechnicianAvailability();
            });
            document.getElementById('jobEditorClose').addEventListener('click', function () {
                editorModal.classList.remove('open');
                document.body.style.overflow = '';
            });

            if (editorModal) {
                editorModal.addEventListener('click', function (e) {
                    if (e.target === editorModal) {
                        editorModal.classList.remove('open');
                        document.body.style.overflow = '';
                    }
                });
            }

            fillEditorPhone();
            setTechnicianAvailability('{{ old('technician_id', '') }}');
        })();
    </script>

</x-layouts.app>