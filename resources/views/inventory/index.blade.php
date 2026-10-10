<x-layouts.app :heading="'Inventory control'">
    <style>
        .inventory-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .45); }
        .inventory-modal.open { display: flex; }
        .inventory-modal-box { position: relative; width: min(100%, 560px); max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 16px; background: #fff; color: #1e293b; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
        .inventory-modal-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border: 0; border-radius: 8px; background: #f1f5f9; color: #1e293b; font-size: 18px; line-height: 1; cursor: pointer; }
        .tm-layout .inventory-count { display: inline-flex; margin-top: 8px; border-radius: 9999px; background: #d1fae5 !important; color: #065f46 !important; padding: 4px 12px; font-size: 12px; font-weight: 600; }
        .tm-layout .inventory-low-stock-row,
        .tm-layout .inventory-low-stock-row td { background: #fff7ed !important; color: #1e293b !important; }
        .tm-layout .inventory-low-stock-row .inventory-stock-value { color: #be123c !important; }
        .tm-layout .inventory-availability { display: inline-block; border-radius: 9999px; padding: 4px 8px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .tm-layout .inventory-availability-available { background: #d1fae5 !important; color: #065f46 !important; }
        .tm-layout .inventory-availability-low { background: #fef3c7 !important; color: #92400e !important; }
        .tm-layout .inventory-availability-out { background: #ffe4e6 !important; color: #9f1239 !important; }
        .tm-layout .inventory-delete-notice { border-radius: 8px; background: #fffbeb !important; color: #92400e !important; padding: 12px; font-size: 14px; }
    </style>

    <div class="space-y-6">
        <section class="card p-6">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="brand">Inventory list</h2>
                    <p class="text-sm text-slate-500">Keep stock levels simple and easy to track.</p>
                    <span class="inventory-count">
                        {{ $items->total() }} items
                    </span>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <form id="inventorySearchForm" action="{{ route('inventory.index') }}" method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div>
                            <label for="inventorySearch" class="label">Search items</label>
                            <input id="inventorySearch"
                                   name="search"
                                   type="search"
                                   class="field mt-1"
                                   value="{{ request('search') }}"
                                   placeholder="Item, SKU, or category"
                                   autocomplete="off">
                        </div>
                        <div>
                            <label for="inventoryAvailability" class="label">Availability</label>
                            <select id="inventoryAvailability" name="availability" class="field mt-1">
                                <option value="all" @selected($availability === 'all')>All stock</option>
                                <option value="available" @selected($availability === 'available')>Available</option>
                                <option value="low" @selected($availability === 'low')>Low stock</option>
                                <option value="out" @selected($availability === 'out')>Out of stock</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">Search / filter</button>
                    </form>
                    @if(request()->filled('search') || $availability !== 'all')
                        <a href="{{ route('inventory.index') }}" class="rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-200">Clear</a>
                    @endif
                    <button type="button" id="newInventoryButton" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">
                        Add item
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-slate-500">
                        <tr>
                            <th class="py-3">Item</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Availability</th>
                            <th>Reorder</th>
                            <th>Unit cost</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="cursor-pointer border-b border-slate-100 hover:bg-slate-50 {{ $item->isLow() ? 'inventory-low-stock-row' : '' }}"
                                data-inventory-row
                                data-item-id="{{ $item->id }}"
                                data-item-sku="{{ $item->sku }}"
                                data-item-name="{{ $item->name }}"
                                data-item-category="{{ $item->category ?? '' }}"
                                data-item-stock="{{ $item->stock }}"
                                data-item-reorder="{{ $item->reorder_level }}"
                                data-item-price="{{ $item->unit_price }}"
                                data-item-has-job-parts="{{ $item->job_parts_count > 0 ? 'true' : 'false' }}">
                                <td class="py-4 font-semibold">{{ $item->name }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->category ?: 'General' }}</td>
                                <td class="inventory-stock-value font-bold {{ $item->isLow() ? 'text-rose-600' : 'text-slate-700' }}">{{ $item->stock }}</td>
                                <td>
                                    @if($item->stock === 0)
                                        <span class="inventory-availability inventory-availability-out">Out of stock</span>
                                    @elseif($item->isLow())
                                        <span class="inventory-availability inventory-availability-low">Low stock</span>
                                    @else
                                        <span class="inventory-availability inventory-availability-available">Available</span>
                                    @endif
                                </td>
                                <td>{{ $item->reorder_level }}</td>
                                <td>₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">
                                    <button type="button"
                                            data-edit-item="{{ $item->id }}"
                                            class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-500">
                                    {{ request()->filled('search') || $availability !== 'all' ? 'No inventory items match these search and filter options.' : 'No inventory items recorded yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </section>
    </div>

    @if($items->count())
        <div id="inventoryDetailsModal" class="inventory-modal" role="dialog" aria-modal="true" aria-label="Inventory item details">
            <div class="inventory-modal-box">
                <button type="button" class="inventory-modal-close" data-close-inventory-modal aria-label="Close">&times;</button>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Inventory item</p>
                <h2 id="inventoryDetailsName" class="mt-2 text-2xl font-bold text-slate-800"></h2>
                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="font-bold text-slate-500">SKU</dt><dd id="inventoryDetailsSku" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Category</dt><dd id="inventoryDetailsCategory" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Stock on hand</dt><dd id="inventoryDetailsStock" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Availability</dt><dd id="inventoryDetailsAvailability" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Reorder point</dt><dd id="inventoryDetailsReorder" class="mt-1"></dd></div>
                    <div><dt class="font-bold text-slate-500">Unit price</dt><dd id="inventoryDetailsPrice" class="mt-1"></dd></div>
                </dl>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-close-inventory-modal class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-700">Close</button>
                    <button type="button" id="inventoryDetailsEdit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Edit item</button>
                </div>
            </div>
        </div>
    @endif

    <div id="inventoryEditorModal" class="inventory-modal" role="dialog" aria-modal="true" aria-labelledby="inventoryEditorTitle">
        <div class="inventory-modal-box">
            <button type="button" class="inventory-modal-close" data-close-inventory-modal aria-label="Close">&times;</button>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Inventory control</p>
            <h2 id="inventoryEditorTitle" class="mt-2 text-2xl font-bold text-slate-800">Add item</h2>
            <p id="inventoryEditorDescription" class="mb-5 mt-1 text-sm text-slate-500">Add a replacement part or accessory.</p>

            <form id="inventoryEditorForm" action="{{ route('inventory.store') }}" method="POST" class="space-y-3">
                @csrf
                <input id="inventoryEditorMethod" type="hidden" name="_method" value="POST">
                <input id="inventoryEditorId" type="hidden" name="item_id" value="{{ old('item_id', '') }}">

                <div>
                    <label for="inventoryEditorSku" class="label">SKU / item code</label>
                    <input id="inventoryEditorSku" class="field mt-1" value="{{ old('sku') }}" placeholder="Generated automatically when saved" readonly>
                    <p class="mt-1 text-xs text-slate-500">Assigned automatically when the item is saved.</p>
                </div>
                <div>
                    <label for="inventoryEditorName" class="label">Item name</label>
                    <input id="inventoryEditorName" class="field mt-1" name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inventoryEditorCategory" class="label">Category</label>
                    <select id="inventoryEditorCategory" class="field mt-1" name="category" required>
                        <option value="">Select a category</option>
                        @foreach(\App\Models\InventoryItem::CATEGORIES as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="inventoryEditorStock" class="label">Stock on hand</label>
                        <input id="inventoryEditorStock" class="field mt-1" name="stock" type="number" min="0" value="{{ old('stock', 0) }}" required>
                        @error('stock')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="inventoryEditorReorder" class="label">Reorder point</label>
                        <input id="inventoryEditorReorder" class="field mt-1" name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 5) }}" required>
                        @error('reorder_level')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="inventoryEditorPrice" class="label">Unit price (₱)</label>
                    <input id="inventoryEditorPrice" class="field mt-1" name="unit_price" type="number" step=".01" min="0" value="{{ old('unit_price', 0) }}" required>
                    @error('unit_price')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <p id="inventoryDeleteNotice" class="inventory-delete-notice hidden">
                    This item is used by a service job and cannot be deleted, so job records are preserved.
                </p>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                    <button type="button" id="deleteInventoryButton" class="hidden rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 hover:bg-rose-100">Delete item</button>
                    <div class="flex justify-end gap-3 sm:ml-auto">
                        <button type="button" data-close-inventory-modal class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-700">Cancel</button>
                        <button type="submit" id="inventoryEditorSubmit" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">Add item</button>
                    </div>
                </div>
            </form>
            <form id="inventoryDeleteForm" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <script>
        (function () {
            const rows = document.querySelectorAll('[data-inventory-row]');
            const detailsModal = document.getElementById('inventoryDetailsModal');
            const editorModal = document.getElementById('inventoryEditorModal');
            const editorForm = document.getElementById('inventoryEditorForm');
            const editorMethod = document.getElementById('inventoryEditorMethod');
            const editorId = document.getElementById('inventoryEditorId');
            const editorTitle = document.getElementById('inventoryEditorTitle');
            const editorDescription = document.getElementById('inventoryEditorDescription');
            const editorSubmit = document.getElementById('inventoryEditorSubmit');
            const editorSku = document.getElementById('inventoryEditorSku');
            const editorName = document.getElementById('inventoryEditorName');
            const editorCategory = document.getElementById('inventoryEditorCategory');
            const editorStock = document.getElementById('inventoryEditorStock');
            const editorReorder = document.getElementById('inventoryEditorReorder');
            const editorPrice = document.getElementById('inventoryEditorPrice');
            const deleteButton = document.getElementById('deleteInventoryButton');
            const deleteNotice = document.getElementById('inventoryDeleteNotice');
            const deleteForm = document.getElementById('inventoryDeleteForm');
            let activeItemRow = null;

            function closeModals() {
                if (detailsModal) detailsModal.classList.remove('open');
                if (editorModal) editorModal.classList.remove('open');
                document.body.style.overflow = '';
            }

            function openDetails(row) {
                if (!detailsModal) return;
                activeItemRow = row;
                const stock = Number(row.dataset.itemStock);
                const reorder = Number(row.dataset.itemReorder);
                document.getElementById('inventoryDetailsName').textContent = row.dataset.itemName;
                document.getElementById('inventoryDetailsSku').textContent = row.dataset.itemSku;
                document.getElementById('inventoryDetailsCategory').textContent = row.dataset.itemCategory || 'General';
                document.getElementById('inventoryDetailsStock').textContent = String(stock);
                document.getElementById('inventoryDetailsAvailability').textContent = stock === 0
                    ? 'Out of stock'
                    : stock <= reorder ? 'Low stock' : 'Available';
                document.getElementById('inventoryDetailsReorder').textContent = String(reorder);
                document.getElementById('inventoryDetailsPrice').textContent = '₱' + Number(row.dataset.itemPrice).toFixed(2);
                detailsModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function openEditor(row = null) {
                activeItemRow = row;
                editorForm.action = row
                    ? '{{ url('/admin/inventory') }}/' + row.dataset.itemId
                    : '{{ route('inventory.store') }}';
                editorMethod.value = row ? 'PATCH' : 'POST';
                editorId.value = row ? row.dataset.itemId : '';
                editorTitle.textContent = row ? 'Edit item' : 'Add item';
                editorDescription.textContent = row ? 'Update inventory details and stock levels.' : 'Add a replacement part or accessory.';
                editorSubmit.textContent = row ? 'Save changes' : 'Add item';
                editorSku.value = row ? row.dataset.itemSku : '';
                editorName.value = row ? row.dataset.itemName : '';
                editorCategory.value = row ? row.dataset.itemCategory : '';
                editorStock.value = row ? row.dataset.itemStock : '0';
                editorReorder.value = row ? row.dataset.itemReorder : '5';
                editorPrice.value = row ? row.dataset.itemPrice : '0';
                deleteButton.classList.toggle('hidden', !row);
                const hasJobParts = row && row.dataset.itemHasJobParts === 'true';
                deleteButton.disabled = Boolean(hasJobParts);
                deleteButton.classList.toggle('cursor-not-allowed', Boolean(hasJobParts));
                deleteButton.classList.toggle('opacity-50', Boolean(hasJobParts));
                deleteNotice.classList.toggle('hidden', !hasJobParts);
                deleteForm.action = row ? '{{ url('/admin/inventory') }}/' + row.dataset.itemId : '';
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

            document.querySelectorAll('[data-edit-item]').forEach(button => {
                button.addEventListener('click', () => {
                    const row = document.querySelector('[data-item-id="' + button.dataset.editItem + '"]');
                    openEditor(row);
                });
            });

            document.querySelectorAll('[data-close-inventory-modal]').forEach(button => {
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

            document.getElementById('newInventoryButton').addEventListener('click', () => openEditor());
            const detailsEditButton = document.getElementById('inventoryDetailsEdit');
            if (detailsEditButton) detailsEditButton.addEventListener('click', () => openEditor(activeItemRow));

            deleteButton.addEventListener('click', () => {
                if (deleteButton.disabled || !activeItemRow) return;
                if (window.confirm('Delete this inventory item? This action cannot be undone.')) deleteForm.submit();
            });

            const oldItemId = @json(old('item_id', ''));
            @if($errors->any())
                @if(old('item_id'))
                    const oldItemRow = document.querySelector('[data-item-id="' + oldItemId + '"]');
                    openEditor(oldItemRow);
                @else
                    openEditor();
                @endif
                editorName.value = @json(old('name', ''));
                editorCategory.value = @json(old('category', ''));
                editorStock.value = @json(old('stock', 0));
                editorReorder.value = @json(old('reorder_level', 5));
                editorPrice.value = @json(old('unit_price', 0));
            @endif
        })();
    </script>
</x-layouts.app>
