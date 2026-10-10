<x-layouts.app :heading="'Customer directory'">
    <style>
        .customer-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .45); }
        .customer-modal.open { display: flex; }
        .customer-modal-box { position: relative; width: min(100%, 560px); max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 16px; background: #fff; color: #1e293b; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
        .customer-modal-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border: 0; border-radius: 8px; background: #f1f5f9; color: #1e293b; font-size: 18px; line-height: 1; cursor: pointer; }
        .customer-account-notice { border: 1px solid #a7f3d0; border-radius: 12px; background: #ecfdf5; padding: 16px; color: #065f46; }
        .customer-account-notice * { color: #065f46 !important; }
    </style>

    <div class="space-y-6">
        @if(session('customer_account_created'))
            <section class="customer-account-notice">
                <h2 class="font-bold">Online customer account ready</h2>
                <p class="mt-1 text-sm">The customer account was created with the password entered in the form. The customer can now sign in using their email and password.</p>
            </section>
        @endif

        <section class="card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="brand">Customers</h2>
                    <p class="text-sm text-slate-500">Residential, commercial, and corporate clients.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <form id="customerSearchForm" action="{{ route('customers.index') }}" method="GET" class="flex gap-2">
                        <div>
                            <label for="customerSearch" class="label">Search customers</label>
                            <input id="customerSearch"
                                   name="search"
                                   type="search"
                                   class="field mt-1"
                                   value="{{ request('search') }}"
                                   placeholder="Name, phone, email, or address"
                                   autocomplete="off">
                        </div>
                        <button type="submit" class="mt-auto rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Search</button>
                    </form>

                    @if(request()->filled('search'))
                        <a href="{{ route('customers.index') }}" class="rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-200">Clear</a>
                    @endif

                    <button type="button" id="newCustomerButton" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                        Add customer
                    </button>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-3">Customer</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Address</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                                data-customer-row
                                data-customer-id="{{ $customer->id }}"
                                data-customer-name="{{ $customer->name }}"
                                data-customer-phone="{{ $customer->phone }}"
                                data-customer-email="{{ $customer->email ?? '' }}"
                                data-customer-address="{{ $customer->address }}"
                                data-customer-type="{{ $customer->type }}"
                                data-customer-has-jobs="{{ $customer->jobs_count > 0 ? 'true' : 'false' }}">
                                <td class="py-4 font-semibold">{{ $customer->name }}</td>
                                <td>{{ $customer->phone }}<br><span class="text-xs text-slate-500">{{ $customer->email ?: 'No email' }}</span></td>
                                <td><span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ $customer->type }}</span></td>
                                <td class="max-w-52 truncate">{{ $customer->address }}</td>
                                <td class="text-right">
                                    <button type="button"
                                            data-edit-customer="{{ $customer->id }}"
                                            class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-500">
                                    {{ request()->filled('search') ? 'No customers match your search.' : 'No customers yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $customers->links() }}</div>
        </section>
    </div>

    @if($customers->count())
        <div id="customerDetailsModal" class="customer-modal" role="dialog" aria-modal="true" aria-label="Customer details">
            <div class="customer-modal-box">
                <button type="button" class="customer-modal-close" data-close-customer-modal aria-label="Close">&times;</button>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Customer details</p>
                <h2 id="customerDetailsName" class="mt-2 text-2xl font-bold text-slate-800"></h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="font-bold text-slate-500">Phone</dt><dd id="customerDetailsPhone" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Email</dt><dd id="customerDetailsEmail" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Customer type</dt><dd id="customerDetailsType" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Service address</dt><dd id="customerDetailsAddress" class="mt-1 whitespace-pre-wrap"></dd></div>
                </dl>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-close-customer-modal class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-700">Close</button>
                    <button type="button" id="customerDetailsEdit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Edit customer</button>
                </div>
            </div>
        </div>
    @endif

    <div id="customerEditorModal" class="customer-modal" role="dialog" aria-modal="true" aria-labelledby="customerEditorTitle">
        <div class="customer-modal-box">
            <button type="button" class="customer-modal-close" data-close-customer-modal aria-label="Close">&times;</button>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Customer directory</p>
            <h2 id="customerEditorTitle" class="mt-2 text-2xl font-bold text-slate-800">Add customer</h2>
            <p id="customerEditorDescription" class="mb-5 mt-1 text-sm text-slate-500">Save the client before booking work.</p>

            <form id="customerEditorForm" action="{{ route('customers.store') }}" method="POST" class="space-y-3">
                @csrf
                <input id="customerEditorMethod" type="hidden" name="_method" value="POST">
                <input id="customerEditorId" type="hidden" name="customer_id" value="{{ old('customer_id', '') }}">

                <div>
                    <label for="customerEditorName" class="label">Full name / business</label>
                    <input id="customerEditorName" class="field mt-1" name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="customerEditorPhone" class="label">Phone number</label>
                    <input id="customerEditorPhone" class="field mt-1" name="phone" value="{{ old('phone') }}" required maxlength="50">
                    @error('phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="customerEditorEmail" class="label">Email <span id="customerEmailRequirement">(required for online login)</span></label>
                    <input id="customerEditorEmail" class="field mt-1" name="email" type="email" value="{{ old('email') }}" maxlength="255">
                    <p id="customerEmailHint" class="mt-1 text-xs text-slate-500">A customer login account will be created using this email.</p>
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div id="customerPasswordGroup">
                    <label for="customerEditorPassword" class="label">Customer login password</label>
                    <input id="customerEditorPassword" class="field mt-1" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="255">
                    <p class="mt-1 text-xs text-slate-500">Enter a password with at least 8 characters for the customer to use when signing in.</p>
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="customerEditorAddress" class="label">Service address</label>
                    <textarea id="customerEditorAddress" class="field mt-1" name="address" rows="3" required>{{ old('address') }}</textarea>
                    @error('address')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="customerEditorType" class="label">Customer type</label>
                    <select id="customerEditorType" class="field mt-1" name="type" required>
                        @foreach(['Residential', 'Commercial', 'Corporate'] as $type)
                            <option value="{{ $type }}" @selected(old('type', 'Residential') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <p id="customerDeleteNotice" class="hidden rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    This customer has service jobs and cannot be deleted, so their job history is preserved.
                </p>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                    <button type="button" id="deleteCustomerButton" class="hidden rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 hover:bg-rose-100">Delete customer</button>
                    <div class="flex justify-end gap-3 sm:ml-auto">
                        <button type="button" data-close-customer-modal class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">Cancel</button>
                        <button type="submit" id="customerEditorSubmit" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">Save customer</button>
                    </div>
                </div>
            </form>
            <form id="customerDeleteForm" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        (function () {
            const rows = document.querySelectorAll('[data-customer-row]');
            const detailsModal = document.getElementById('customerDetailsModal');
            const editorModal = document.getElementById('customerEditorModal');
            const editorForm = document.getElementById('customerEditorForm');
            const editorMethod = document.getElementById('customerEditorMethod');
            const editorId = document.getElementById('customerEditorId');
            const editorTitle = document.getElementById('customerEditorTitle');
            const editorDescription = document.getElementById('customerEditorDescription');
            const editorSubmit = document.getElementById('customerEditorSubmit');
            const editorName = document.getElementById('customerEditorName');
            const editorPhone = document.getElementById('customerEditorPhone');
            const editorEmail = document.getElementById('customerEditorEmail');
            const emailRequirement = document.getElementById('customerEmailRequirement');
            const emailHint = document.getElementById('customerEmailHint');
            const passwordGroup = document.getElementById('customerPasswordGroup');
            const editorPassword = document.getElementById('customerEditorPassword');
            const editorAddress = document.getElementById('customerEditorAddress');
            const editorType = document.getElementById('customerEditorType');
            const deleteButton = document.getElementById('deleteCustomerButton');
            const deleteNotice = document.getElementById('customerDeleteNotice');
            const deleteForm = document.getElementById('customerDeleteForm');
            let activeCustomerRow = null;

            function closeModals() {
                if (detailsModal) detailsModal.classList.remove('open');
                if (editorModal) editorModal.classList.remove('open');
                document.body.style.overflow = '';
            }

            function openDetails(row) {
                if (!detailsModal) return;
                activeCustomerRow = row;
                document.getElementById('customerDetailsName').textContent = row.dataset.customerName;
                document.getElementById('customerDetailsPhone').textContent = row.dataset.customerPhone;
                document.getElementById('customerDetailsEmail').textContent = row.dataset.customerEmail || 'No email';
                document.getElementById('customerDetailsType').textContent = row.dataset.customerType;
                document.getElementById('customerDetailsAddress').textContent = row.dataset.customerAddress;
                detailsModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function openEditor(row = null) {
                activeCustomerRow = row;
                editorForm.action = row
                    ? '{{ url('/admin/customers') }}/' + row.dataset.customerId
                    : '{{ route('customers.store') }}';
                editorMethod.value = row ? 'PATCH' : 'POST';
                editorId.value = row ? row.dataset.customerId : '';
                editorTitle.textContent = row ? 'Edit customer' : 'Add customer';
                editorDescription.textContent = row ? 'Update the customer contact and service details.' : 'Save the client before booking work.';
                editorSubmit.textContent = row ? 'Save changes' : 'Save customer';
                editorEmail.required = !row;
                emailRequirement.classList.toggle('hidden', Boolean(row));
                emailHint.classList.toggle('hidden', Boolean(row));
                passwordGroup.classList.toggle('hidden', Boolean(row));
                editorPassword.required = !row;
                editorPassword.value = '';
                editorName.value = row ? row.dataset.customerName : '';
                editorPhone.value = row ? row.dataset.customerPhone : '';
                editorEmail.value = row ? row.dataset.customerEmail : '';
                editorAddress.value = row ? row.dataset.customerAddress : '';
                editorType.value = row ? row.dataset.customerType : 'Residential';
                deleteButton.classList.toggle('hidden', !row);
                const hasJobs = row && row.dataset.customerHasJobs === 'true';
                deleteButton.disabled = Boolean(hasJobs);
                deleteButton.classList.toggle('cursor-not-allowed', Boolean(hasJobs));
                deleteButton.classList.toggle('opacity-50', Boolean(hasJobs));
                deleteNotice.classList.toggle('hidden', !hasJobs);
                deleteForm.action = row ? '{{ url('/admin/customers') }}/' + row.dataset.customerId : '';
                if (detailsModal) detailsModal.classList.remove('open');
                editorModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            rows.forEach(row => {
                row.addEventListener('click', event => {
                    if (event.target.closest('button')) return;
                    openDetails(row);
                });
            });

            document.querySelectorAll('[data-edit-customer]').forEach(button => {
                button.addEventListener('click', () => {
                    const row = document.querySelector('[data-customer-id="' + button.dataset.editCustomer + '"]');
                    openEditor(row);
                });
            });

            document.querySelectorAll('[data-close-customer-modal]').forEach(button => {
                button.addEventListener('click', closeModals);
            });

            [detailsModal, editorModal].filter(Boolean).forEach(modal => {
                modal.addEventListener('click', event => {
                    if (event.target === modal) closeModals();
                });
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeModals();
            });

            document.getElementById('newCustomerButton').addEventListener('click', () => openEditor());
            const detailsEditButton = document.getElementById('customerDetailsEdit');
            if (detailsEditButton) detailsEditButton.addEventListener('click', () => openEditor(activeCustomerRow));

            deleteButton.addEventListener('click', () => {
                if (deleteButton.disabled || !activeCustomerRow) return;
                if (window.confirm('Delete this customer? This action cannot be undone.')) deleteForm.submit();
            });

            const oldCustomerId = @json(old('customer_id', ''));
            @if($errors->any() && !$errors->has('customer'))
                @if(old('customer_id'))
                    const oldCustomerRow = document.querySelector('[data-customer-id="' + oldCustomerId + '"]');
                    openEditor(oldCustomerRow);
                @else
                    openEditor();
                @endif
                editorName.value = @json(old('name', ''));
                editorPhone.value = @json(old('phone', ''));
                editorEmail.value = @json(old('email', ''));
                editorAddress.value = @json(old('address', ''));
                editorType.value = @json(old('type', 'Residential'));
            @endif
        })();
    </script>
</x-layouts.app>
