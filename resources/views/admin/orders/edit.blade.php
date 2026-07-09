{{-- FILE: resources/views/admin/orders/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Edit Order — ' . $order->order_ref)
@section('page-title', 'Edit Order — ' . $order->order_ref)
@section('page-sub', 'Add, remove, or adjust items. Changes are logged and stock reservations updated automatically.')

@section('header-actions')
<a href="{{ route('admin.orders.show', $order->id) }}" class="text-xs font-body text-gray-400 hover:text-navy flex items-center gap-1 transition-colors">
  ← Back to order
</a>
@endsection

@section('content')

<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-xs font-body text-amber-700">
  ⚠ Editing this order will release stock for any removed parts back to Available, and reserve stock for any newly added parts. This is logged in the order's edit history.
</div>

<form method="POST" action="{{ route('admin.orders.update', $order->id) }}" id="orderEditForm">
@csrf
@method('PUT')

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-5">
  <div class="flex items-center justify-between px-5 py-3 bg-navy">
    <div>
      <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Order Items</h2>
      <p class="text-xs text-gray-400 mt-0.5">Search to add parts/services, or remove existing lines below.</p>
    </div>
    <div class="relative">
      <input type="text" id="itemSearchInput" placeholder="Search parts or services to add..."
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold w-72">
      <div id="itemSearchSuggestions" class="absolute right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-96 hidden max-h-72 overflow-y-auto"></div>
    </div>
  </div>

  <div id="itemsContainer" class="divide-y divide-gray-100 p-4 space-y-2">
    {{-- rows injected by JS from EXISTING_ITEMS on load, and appended to when searching --}}
  </div>

  <div class="flex justify-end px-5 py-4 bg-gray-50 border-t border-gray-200">
    <div class="w-64">
      <div class="flex justify-between text-sm font-body py-1">
        <span class="font-display font-700 text-navy uppercase tracking-wide">TOTAL:</span>
        <span id="totalDisplay" class="font-display font-800 text-navy text-lg">—</span>
      </div>
    </div>
  </div>
</div>

<div class="flex gap-3 justify-end pb-8">
  <a href="{{ route('admin.orders.show', $order->id) }}"
    class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
    Cancel
  </a>
  <button type="submit"
    class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
    Save Changes
  </button>
</div>

</form>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const ORDER_LOCATION = "{{ $order->location ?? $order->customer_country ?? 'Waxahachie TX' }}";
const CURRENCY_SYMBOLS = { NGN: '₦', GHS: 'GH₵', USD: '$' };
const ORDER_CURRENCY = "{{ $order->currency_code ?? 'USD' }}";

// Existing items on this order, loaded from the server — each becomes
// a row exactly like a freshly-searched-and-added item would, so the
// remove/re-add flow is identical either way.
const EXISTING_ITEMS = {!! $existingItemsJson !!};

let rowCount = 0;
const rows = {}; // rowId -> { item_type, id, name, code, price }

function fmtPrice(price) {
    const sym = CURRENCY_SYMBOLS[ORDER_CURRENCY] || '$';
    return sym + (ORDER_CURRENCY === 'NGN' ? Math.round(price).toLocaleString() : Number(price).toFixed(2));
}

function addRow(item) {
    const i = rowCount++;
    rows[i] = item;

    const row = document.createElement('div');
    row.id = 'item-row-' + i;
    row.className = 'flex items-center justify-between gap-3 bg-gray-50 rounded-xl p-3 border border-gray-200';
    row.innerHTML = `
        <div class="flex-1 min-w-0">
            <div class="font-display font-700 text-navy text-sm truncate">${item.name}</div>
            <div class="text-xs text-gray-400 font-mono">${item.code || ''} ${item.item_type === 'service' ? '· Service' : ''}</div>
        </div>
        <div class="font-display font-700 text-navy text-sm whitespace-nowrap">${fmtPrice(item.price)}</div>
        <input type="hidden" name="items[${i}][item_type]" value="${item.item_type}">
        <input type="hidden" name="items[${i}][id]" value="${item.item_type === 'service' ? item.service_id : item.part_id}">
        <button type="button" onclick="removeRow(${i})" class="text-red-400 hover:text-red-600 text-lg leading-none flex-shrink-0">×</button>
    `;
    document.getElementById('itemsContainer').appendChild(row);
    updateTotal();
}

function removeRow(i) {
    document.getElementById('item-row-' + i)?.remove();
    delete rows[i];
    updateTotal();
}

function updateTotal() {
    let total = 0;
    Object.values(rows).forEach(r => { total += parseFloat(r.price || 0); });
    document.getElementById('totalDisplay').textContent = fmtPrice(total);
}

// ── Load existing items on page load ──
EXISTING_ITEMS.forEach(item => addRow(item));

// ── Search to add new items — same live search endpoint Place Order uses ──
let searchTimer = null;
const searchInput = document.getElementById('itemSearchInput');
const searchBox    = document.getElementById('itemSearchSuggestions');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value;
    if (q.length < 1) { searchBox.classList.add('hidden'); return; }
    searchTimer = setTimeout(() => runSearch(q), 300);
});

async function runSearch(q) {
    searchBox.classList.remove('hidden');
    searchBox.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.orders.place.search-parts') }}?q=${encodeURIComponent(q)}&location=${encodeURIComponent(ORDER_LOCATION)}`);
        const data = await res.json();
        const results = data.parts || [];

        if (results.length === 0) {
            searchBox.innerHTML = '<div class="px-3 py-3 text-xs text-gray-400">No matching parts or services found.</div>';
            return;
        }

        searchBox.innerHTML = results.map(p => {
            const priceLocal = p.price_local ?? p.price_usd ?? 0;
            const label = p.item_type === 'service' ? `${p.part_name} (Service)` : `${p.part_name} — ${p.brand || ''} ${p.model || ''}`;
            return `
            <div onclick='selectSearchResult(${JSON.stringify(p).replace(/'/g, "&#39;")})'
                class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100">
                <div class="font-700 text-navy text-xs">${label}</div>
                <div class="text-xs text-gray-400">${p.part_code || ''} · ${fmtPrice(priceLocal)}${p.stock_qty !== null && p.stock_qty !== undefined ? ' · ' + p.stock_qty + ' in stock' : ''}</div>
            </div>`;
        }).join('');
    } catch (e) {
        searchBox.innerHTML = '<div class="px-3 py-3 text-xs text-red-500">Search failed — try again.</div>';
    }
}

function selectSearchResult(p) {
    addRow({
        item_type: p.item_type,
        part_id: p.item_type === 'part' ? p.id : null,
        service_id: p.item_type === 'service' ? p.id : null,
        name: p.part_name,
        code: p.part_code,
        price: p.price_local ?? p.price_usd ?? 0,
    });
    searchInput.value = '';
    searchBox.classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!searchBox.contains(e.target) && e.target !== searchInput) {
        searchBox.classList.add('hidden');
    }
});

document.getElementById('orderEditForm').addEventListener('submit', function(e) {
    if (Object.keys(rows).length === 0) {
        e.preventDefault();
        alert('An order needs at least one item — remove the whole order instead if it should be cancelled.');
    }
});
</script>

@endsection
