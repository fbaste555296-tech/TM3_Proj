<x-layouts.app :heading="'Billing & payments'">

    <style>
        .invoice-edit-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .5); }
        .invoice-edit-modal.open { display: flex; }
        .invoice-edit-box { position: relative; width: min(100%, 440px); border-radius: 16px; background: #fff; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
    </style>

    <div class="card p-6">
        <div class="flex justify-between">
            <div>
                <h2 class="brand">Invoices</h2>
                <p class="mb-5 text-sm text-slate-500">Invoices are automatically created when a job is completed.</p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span style="background:none;color:#64748b;font-size:12px;font-weight:600;padding:0;height:fit-content;">Digital receipts</span>
                <a href="{{ route('invoices.export') }}" class="tm-button rounded-lg px-4 py-2.5 text-sm font-bold">
                    Export Excel
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('invoices.index') }}" class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label for="invoiceSearch" class="label">Search customer</label>
                <input id="invoiceSearch"
                       name="search"
                       type="search"
                       class="field mt-1"
                       value="{{ request('search') }}"
                       placeholder="Customer name or phone number..."
                       autocomplete="off">
            </div>
            <button type="submit" class="tm-button rounded-lg px-4 py-2.5 text-sm font-bold">Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('invoices.index') }}" class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Invoice</th>
                        <th class="px-3 py-3">Customer</th>
                        <th class="px-3 py-3">Phone</th>
                        <th class="px-3 py-3">Reported issue</th>
                        <th class="px-3 py-3">Total</th>
                        <th class="px-3 py-3">Paid</th>
                        <th class="px-3 py-3">Balance</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $paidAmount = (float) $invoice->paid_amount;
                            $total = (float) $invoice->total;
                            $balance = max(0, round($total - $paidAmount, 2));
                            $isPaid  = $balance <= 0;
                            $paymentStatus = $isPaid ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');
                            $phone   = $invoice->job?->customer?->phone;
                            $issue   = trim((string) ($invoice->job?->notes ?? ''));
                            $isLong  = \Illuminate\Support\Str::length($issue) > 60;
                        @endphp
                        <tr class="border-b border-slate-100 align-top">
                            <td class="whitespace-nowrap px-3 py-4 font-semibold">
                                {{ $invoice->invoice_number }}<br>
                                <span class="text-xs font-normal text-slate-500">{{ $invoice->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="px-3 py-4">{{ $invoice->job?->customer?->name ?? 'Unknown customer' }}</td>
                            <td class="whitespace-nowrap px-3 py-4">
                                @if($phone)
                                    <a href="tel:{{ $phone }}" class="tm-link font-semibold">{{ $phone }}</a>
                                @else
                                    <span class="text-slate-400">No phone</span>
                                @endif
                            </td>

                            {{-- Reported issue --}}
                            <td class="px-3 py-4" style="min-width:200px;max-width:280px;">
                                @if($issue === '')
                                    <span class="text-slate-400">No issue reported</span>
                                @elseif($isLong)
                                    <details>
                                        <summary class="cursor-pointer list-none">
                                            <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($issue, 60) }}</span>
                                            <span class="tm-link whitespace-nowrap text-xs font-bold">Show full</span>
                                        </summary>
                                        <p class="mt-2 whitespace-pre-line text-slate-700">{{ $issue }}</p>
                                    </details>
                                @else
                                    <span class="whitespace-pre-line text-slate-700">{{ $issue }}</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-4">₱{{ number_format($invoice->total, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-4">₱{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-4 font-semibold">₱{{ number_format($balance, 2) }}</td>
                            <td class="px-3 py-4">
                                {{-- Plain text status, no background color --}}
                                <span style="background:none;color:#1e293b;font-weight:600;padding:0;white-space:nowrap;">
                                    {{ $paymentStatus }}
                                </span>
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex min-w-32 flex-col items-start gap-2">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="tm-button whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold"
                                                data-edit-invoice
                                                data-update-url="{{ route('invoices.update', $invoice) }}"
                                                data-invoice-number="{{ $invoice->invoice_number }}"
                                                data-total="{{ number_format($total, 2, '.', '') }}"
                                                data-paid="{{ number_format($paidAmount, 2, '.', '') }}">Edit</button>
                                        <form method="POST" action="{{ route('invoices.delete', $invoice) }}" onsubmit="return confirm('Delete this invoice and its payment records?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="whitespace-nowrap rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700">Delete</button>
                                        </form>
                                    </div>
                                    @unless($isPaid)
                                        <details>
                                            <summary class="tm-link cursor-pointer whitespace-nowrap text-xs font-bold">Record payment</summary>
                                        <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="mt-2 flex flex-wrap items-center gap-1">
                                            @csrf
                                            <input class="w-24 rounded border p-1 text-xs" name="amount" type="number" step="0.01" min="0.01"
                                                   max="{{ number_format($balance, 2, '.', '') }}"
                                                   value="{{ number_format($balance, 2, '.', '') }}" required>
                                            <select class="rounded border p-1 text-xs" name="method">
                                                <option>Cash</option>
                                                <option>GCash</option>
                                                <option>Bank Transfer</option>
                                            </select>
                                            <button type="submit" class="tm-button rounded px-2 py-1 text-xs font-semibold">Save</button>
                                        </form>
                                        </details>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-500">No invoices yet. Complete a service job to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>

    <div id="invoiceEditModal" class="invoice-edit-modal" role="dialog" aria-modal="true" aria-labelledby="invoiceEditTitle" aria-hidden="true">
        <div class="invoice-edit-box">
            <button type="button" id="invoiceEditClose" class="absolute right-3 top-3 rounded-lg bg-slate-100 px-2 py-1 text-lg text-slate-700 hover:bg-slate-200" aria-label="Close">×</button>
            <h2 id="invoiceEditTitle" class="brand text-lg font-bold text-slate-800">Record invoice payment</h2>
            <p id="invoiceEditNumber" class="mt-1 text-sm text-slate-500"></p>
            <form id="invoiceEditForm" method="POST" class="mt-5">
                @csrf
                @method('PATCH')
                <div class="grid gap-3 rounded-xl bg-stone-50 p-4 text-sm sm:grid-cols-2">
                    <p>Total due <strong id="invoiceEditTotal" class="block text-slate-800"></strong></p>
                    <p>Previously paid <strong id="invoiceEditPreviouslyPaid" class="block text-slate-800"></strong></p>
                    <p>Paid after payment <strong id="invoiceEditPaid" class="block text-slate-800"></strong></p>
                    <p>Remaining balance <strong id="invoiceEditBalance" class="block text-slate-800"></strong></p>
                    <p class="sm:col-span-2">Status <strong id="invoiceEditStatus" class="block text-slate-800"></strong></p>
                </div>
                <div class="mt-4">
                    <label for="invoiceEditAmount" class="label">Amount received now (₱)</label>
                    <input id="invoiceEditAmount" name="amount" class="field mt-1" type="number" min="0" step="0.01" value="0" required>
                </div>
                <div class="mt-4">
                    <label for="invoiceEditMethod" class="label">Payment method</label>
                    <select id="invoiceEditMethod" name="method" class="field mt-1" required>
                        <option>Cash</option>
                        <option>GCash</option>
                        <option>Bank Transfer</option>
                    </select>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="invoiceEditCancel" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="tm-button rounded-lg px-4 py-2 text-sm font-bold">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('invoiceEditModal');
            const form = document.getElementById('invoiceEditForm');
            const amount = document.getElementById('invoiceEditAmount');
            const invoiceNumber = document.getElementById('invoiceEditNumber');
            const totalDisplay = document.getElementById('invoiceEditTotal');
            const previouslyPaidDisplay = document.getElementById('invoiceEditPreviouslyPaid');
            const paidDisplay = document.getElementById('invoiceEditPaid');
            const balanceDisplay = document.getElementById('invoiceEditBalance');
            const statusDisplay = document.getElementById('invoiceEditStatus');
            const closeButton = document.getElementById('invoiceEditClose');
            const cancelButton = document.getElementById('invoiceEditCancel');
            let invoiceTotal = 0;
            let previouslyPaid = 0;

            const formatMoney = value => '₱' + value.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const updatePaymentPreview = () => {
                const received = Math.max(0, Number(amount.value) || 0);
                const paid = Math.min(invoiceTotal, previouslyPaid + received);
                const balance = Math.max(0, invoiceTotal - paid);

                paidDisplay.textContent = formatMoney(paid);
                balanceDisplay.textContent = formatMoney(balance);
                statusDisplay.textContent = balance === 0 ? 'Paid' : (paid > 0 ? 'Partial' : 'Unpaid');
            };

            const closeModal = () => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            };

            document.querySelectorAll('[data-edit-invoice]').forEach(button => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.updateUrl;
                    invoiceTotal = Number(button.dataset.total) || 0;
                    previouslyPaid = Number(button.dataset.paid) || 0;
                    invoiceNumber.textContent = button.dataset.invoiceNumber;
                    totalDisplay.textContent = formatMoney(invoiceTotal);
                    previouslyPaidDisplay.textContent = formatMoney(previouslyPaid);
                    amount.max = Math.max(0, invoiceTotal - previouslyPaid).toFixed(2);
                    amount.value = '0';
                    updatePaymentPreview();
                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                    amount.focus();
                });
            });

            amount.addEventListener('input', updatePaymentPreview);
            closeButton.addEventListener('click', closeModal);
            cancelButton.addEventListener('click', closeModal);
            modal.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modal.classList.contains('open')) closeModal();
            });
        })();
    </script>
</x-layouts.app>