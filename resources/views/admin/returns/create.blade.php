{{-- FILE: resources/views/admin/returns/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Log a Return')
@section('page-title','Log a Return')
@section('page-sub','For a customer return or an internal quality reject — the part goes on Hold until inspected')

@section('content')
<div class="max-w-2xl">
  <form method="POST" action="{{ route('admin.returns.store') }}" id="returnForm">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Return Type</h2>
      <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer">
          <input type="radio" name="return_type" value="customer" class="sr-only peer" checked>
          <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Customer Return</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Part was sold and customer brought it back</div>
          </div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="return_type" value="internal" class="sr-only peer">
          <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Internal Reject</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Quality issue found — never sold</div>
          </div>
        </label>
      </div>
    </div>

    {{-- Linked sale — searches BOTH invoices and orders now, and shows
         the FULL sale (customer + every line item) once matched, with
         a checkbox per item instead of a single-select dropdown, so 1
         or 2 items out of a larger sale can be returned in one go. --}}
    <div class="stat-card mb-5" id="invoiceSection">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Linked Sale (optional)</h2>
      <p class="text-xs text-gray-400 font-body mb-4">Search by invoice/order number, customer name, or phone — covers Manual Invoices, Service Invoices, and Place Orders. Skip if you don't have it handy.</p>
      <input type="text" id="invoiceSearchInput" placeholder="Search invoices and orders..."
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      <div id="invoiceResults" class="mt-2 space-y-1"></div>

      <input type="hidden" name="sale_type" id="saleTypeInput" value="{{ $saleType ?? '' }}">
      <input type="hidden" name="sale_id" id="saleIdInput" value="{{ $prefillSale->id ?? '' }}">

      {{-- Full sale preview — customer info + every line item, shown
           once a sale is matched, so staff visually confirm it's the
           right one before picking what's being returned. --}}
      <div id="salePreview" class="mt-3 {{ $prefillSale ? '' : 'hidden' }} bg-blue-50 border border-blue-100 rounded-xl overflow-hidden">
        <div class="px-3 py-2 bg-blue-100 flex items-center justify-between">
          <div class="text-sm font-body text-blue-900">
            <strong id="salePreviewRef">{{ $prefillSale->ref ?? '' }}</strong>
            <span id="salePreviewTypeBadge" class="text-[10px] uppercase tracking-wide bg-blue-700 text-white px-1.5 py-0.5 rounded ml-1">{{ $saleType ?? '' }}</span>
            — <span id="salePreviewCustomer">{{ $prefillSale->customer_name ?? '' }}</span>
            <span id="salePreviewPhone" class="text-blue-600 text-xs">{{ !empty($prefillSale->customer_phone) ? '(' . $prefillSale->customer_phone . ')' : '' }}</span>
          </div>
          <button type="button" onclick="clearInvoice()" class="text-xs text-blue-700 underline flex-shrink-0">Change</button>
        </div>
        <div id="salePreviewItems" class="divide-y divide-blue-100">
          @foreach($prefillItems as $it)
          <label class="flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-blue-100/50 transition-colors">
            <input type="checkbox" class="sale-item-checkbox rounded border-blue-300"
              value="{{ $it->id }}" data-part-id="{{ $it->part_id ?? '' }}"
              data-label="{{ $it->part_name }} ({{ $it->part_code }})"
              data-line-total="{{ $it->line_total_local ?? 0 }}"
              onchange="onSaleItemToggle(this)">
            <span class="text-sm font-body text-gray-700 flex-1">{{ $it->part_name }} <span class="text-gray-400 text-xs">({{ $it->part_code }}) — Qty {{ $it->qty }}</span></span>
            <span class="text-xs font-mono text-gray-500">{{ number_format($it->line_total_local ?? 0, 2) }}</span>
          </label>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Selected items to return — built up from either the sale
         preview checkboxes above, or the manual part search below.
         Each row here becomes one return record on submit. --}}
    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Item(s) Being Returned *</h2>
      <p class="text-xs text-gray-400 font-body mb-3">Check items above from a linked sale, or search for a part manually below (e.g. for internal rejects or no-invoice cases).</p>

      <div id="selectedItemsList" class="space-y-2 mb-3"></div>
      <div id="noItemsWarning" class="text-xs text-gray-400 font-body mb-3">No items selected yet.</div>

      <input type="text" id="partSearchInput" placeholder="Search parts manually..."
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      <div id="partResults" class="mt-2 space-y-1 hidden"></div>
    </div>

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Reason for Return *</h2>
      <textarea name="reason" rows="3" required placeholder="e.g. Customer says it doesn't fit; wrong gear ratio; engine has a knock noise..."
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"></textarea>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Log Return — Place Part(s) on Hold
      </button>
      <a href="{{ route('admin.returns.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>

@push('scripts')
<script>
// ── Selected items state — rebuilt into hidden inputs before submit ──────
// Keyed by a synthetic key so a manually-searched part and a
// sale-linked item never collide.
let selectedItems = {};

@if($prefillItems->isNotEmpty())
// Nothing pre-checked by default even when a sale is prefilled — staff
// still explicitly picks which item(s) are actually being returned.
@endif

function renderSelectedItems() {
    const list = document.getElementById('selectedItemsList');
    const warning = document.getElementById('noItemsWarning');
    const keys = Object.keys(selectedItems);

    if (keys.length === 0) {
        list.innerHTML = '';
        warning.classList.remove('hidden');
        return;
    }
    warning.classList.add('hidden');

    list.innerHTML = keys.map(key => {
        const it = selectedItems[key];
        return `
        <div class="flex items-center gap-2 bg-green-50 border border-green-100 rounded-lg px-3 py-2">
            <span class="text-sm font-body text-green-800 flex-1">${it.label}</span>
            <span class="text-xs text-gray-400">Refund:</span>
            <div class="relative">
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">₦</span>
                <input type="number" step="0.01" min="0" value="${it.refund}"
                    oninput="updateItemRefund('${key}', this.value)"
                    class="w-24 border border-gray-200 rounded-lg pl-5 pr-2 py-1 text-xs font-body focus:outline-none focus:border-yellow-400">
            </div>
            <button type="button" onclick="removeSelectedItem('${key}')" class="text-red-400 hover:text-red-600 text-xs">✕</button>
        </div>`;
    }).join('');
}

function updateItemRefund(key, value) {
    if (selectedItems[key]) selectedItems[key].refund = parseFloat(value) || 0;
}

function removeSelectedItem(key) {
    delete selectedItems[key];
    // Uncheck the matching sale-item checkbox, if it came from there
    document.querySelectorAll('.sale-item-checkbox').forEach(cb => {
        if (`sale-${cb.value}` === key) cb.checked = false;
    });
    renderSelectedItems();
}

// ── Toggling a checkbox in the sale preview ───────────────────────────────
function onSaleItemToggle(checkbox) {
    const key = `sale-${checkbox.value}`;
    if (checkbox.checked) {
        selectedItems[key] = {
            saleItemId: checkbox.value,
            partId: checkbox.dataset.partId,
            label: checkbox.dataset.label,
            refund: parseFloat(checkbox.dataset.lineTotal) || 0,
        };
    } else {
        delete selectedItems[key];
    }
    renderSelectedItems();
}

// ── Return type toggle — hide sale section for internal rejects ─────────
document.querySelectorAll('input[name="return_type"]').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('invoiceSection').style.display = this.value === 'internal' ? 'none' : '';
        if (this.value === 'internal') clearInvoice();
    });
});

// ── Sale search (invoices + orders, unified) ─────────────────────────────
let invoiceSearchTimer = null;
document.getElementById('invoiceSearchInput').addEventListener('input', function() {
    clearTimeout(invoiceSearchTimer);
    const q = this.value.trim();
    if (!q) { document.getElementById('invoiceResults').innerHTML = ''; return; }
    invoiceSearchTimer = setTimeout(() => searchInvoices(q), 300);
});

async function searchInvoices(q) {
    const box = document.getElementById('invoiceResults');
    box.innerHTML = '<div class="text-xs text-gray-400 font-body">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.returns.search-invoices') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.invoices || data.invoices.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 font-body">No matches.</div>';
            return;
        }
        box.innerHTML = data.invoices.map(sale => `
            <button type="button" onclick="selectSale('${sale.sale_type}', ${sale.id})"
                class="block w-full text-left text-sm font-body border border-gray-200 rounded-lg px-3 py-2 hover:border-gold transition-colors">
                <strong>${sale.ref}</strong>
                <span class="text-[10px] uppercase tracking-wide bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded ml-1">${sale.sale_type}</span>
                — ${sale.customer_name} (${sale.customer_phone || 'no phone'})
            </button>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 font-body">Search failed.</div>';
    }
}

// ── Selecting a sale — loads the FULL sale (customer + every item) ───────
async function selectSale(saleType, saleId) {
    document.getElementById('saleTypeInput').value = saleType;
    document.getElementById('saleIdInput').value = saleId;
    document.getElementById('invoiceResults').innerHTML = '';
    document.getElementById('invoiceSearchInput').value = '';

    const preview = document.getElementById('salePreview');
    const itemsBox = document.getElementById('salePreviewItems');
    itemsBox.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400 font-body">Loading sale...</div>';
    preview.classList.remove('hidden');

    try {
        const res = await fetch(`{{ route('admin.returns.invoice-items') }}?sale_type=${saleType}&sale_id=${saleId}`);
        const data = await res.json();

        document.getElementById('salePreviewTypeBadge').textContent = saleType;

        if (!data.items || data.items.length === 0) {
            itemsBox.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400 font-body">No items found on this sale.</div>';
            return;
        }

        itemsBox.innerHTML = data.items.map(it => `
            <label class="flex items-center gap-2.5 px-3 py-2 cursor-pointer hover:bg-blue-100/50 transition-colors">
                <input type="checkbox" class="sale-item-checkbox rounded border-blue-300"
                    value="${it.id}" data-part-id="${it.part_id || ''}"
                    data-label="${it.part_name} (${it.part_code})"
                    data-line-total="${it.line_total_local || 0}"
                    onchange="onSaleItemToggle(this)">
                <span class="text-sm font-body text-gray-700 flex-1">${it.part_name} <span class="text-gray-400 text-xs">(${it.part_code}) — Qty ${it.qty}</span></span>
                <span class="text-xs font-mono text-gray-500">${Number(it.line_total_local || 0).toFixed(2)}</span>
            </label>
        `).join('');
    } catch (e) {
        itemsBox.innerHTML = '<div class="px-3 py-2 text-xs text-red-500 font-body">Could not load sale items.</div>';
    }
}

function clearInvoice() {
    document.getElementById('saleTypeInput').value = '';
    document.getElementById('saleIdInput').value = '';
    document.getElementById('salePreview').classList.add('hidden');
    document.getElementById('salePreviewItems').innerHTML = '';
    // Drop any selections that came from the sale preview (keep
    // manually-searched parts intact)
    Object.keys(selectedItems).forEach(key => {
        if (key.startsWith('sale-')) delete selectedItems[key];
    });
    renderSelectedItems();
}

// ── Manual part search — for internal rejects / no-invoice returns ──────
let partSearchTimer = null;
document.getElementById('partSearchInput').addEventListener('input', function() {
    clearTimeout(partSearchTimer);
    const q = this.value.trim();
    if (!q) { document.getElementById('partResults').classList.add('hidden'); return; }
    partSearchTimer = setTimeout(() => searchParts(q), 300);
});

async function searchParts(q) {
    const box = document.getElementById('partResults');
    box.classList.remove('hidden');
    box.innerHTML = '<div class="text-xs text-gray-400 font-body">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.returns.search-parts') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.parts || data.parts.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 font-body">No matches.</div>';
            return;
        }
        box.innerHTML = data.parts.map(p => `
            <button type="button" onclick='selectManualPart(${p.id}, "${p.part_name} (${p.part_code}) — ${p.brand} ${p.model}, ${p.location}")'
                class="block w-full text-left text-sm font-body border border-gray-200 rounded-lg px-3 py-2 hover:border-gold transition-colors">
                <strong>${p.part_code}</strong> — ${p.part_name} (${p.brand} ${p.model}) · ${p.status}
            </button>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 font-body">Search failed.</div>';
    }
}

function selectManualPart(id, label) {
    const key = `manual-${id}`;
    selectedItems[key] = { saleItemId: null, partId: id, label, refund: 0 };
    renderSelectedItems();
    document.getElementById('partResults').classList.add('hidden');
    document.getElementById('partSearchInput').value = '';
}

// ── Build hidden items[] inputs right before submit ───────────────────────
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const keys = Object.keys(selectedItems);
    if (keys.length === 0) {
        e.preventDefault();
        alert('Select at least one item to return.');
        return;
    }
    keys.forEach((key, i) => {
        const it = selectedItems[key];
        this.insertAdjacentHTML('beforeend', `
            <input type="hidden" name="items[${i}][part_id]" value="${it.partId}">
            <input type="hidden" name="items[${i}][sale_item_id]" value="${it.saleItemId ?? ''}">
            <input type="hidden" name="items[${i}][refund_amount_local]" value="${it.refund}">
        `);
    });
});

// ── If arriving pre-filled via a "Log Return" link, nothing is
//    auto-checked — staff still explicitly picks the item(s) from the
//    preview above, same as a fresh search. ──
renderSelectedItems();
</script>
@endpush
@endsection
