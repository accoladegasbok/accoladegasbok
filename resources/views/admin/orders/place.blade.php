{{-- FILE: resources/views/admin/orders/place.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Place Order')
@section('page-title', 'Place Order — Walk-in / Phone Customer')
@section('page-sub', 'Same cart-style flow as the customer site, with live stock checking — creates a real Order')

@section('header-actions')
<a href="{{ route('admin.invoices.manual.create') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  Use Manual Invoice Instead
</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.orders.place.store') }}" id="placeOrderForm">
@csrf

<div class="max-w-5xl space-y-5">

  @if($errors->any() || session('error'))
  <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body">
    {{ session('error') }}
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
  @endif

  {{-- Customer Info --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Customer Information</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="col-span-2 sm:col-span-1 relative">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone Number *</label>
        <input type="text" name="customer_phone" id="customerPhoneInput" required placeholder="+1 512 000 0000" autocomplete="off"
          oninput="lookupCustomer(this.value)"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        <div id="customerSuggestions" class="absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-full hidden max-h-56 overflow-y-auto"></div>
      </div>
      <div class="col-span-2 sm:col-span-1">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Customer Name *</label>
        <input type="text" name="customer_name" id="customerNameInput" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email (optional)</label>
        <input type="email" name="customer_email" id="customerEmailInput"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">WhatsApp (if different)</label>
        <input type="text" name="customer_whatsapp" placeholder="Same as phone if blank"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">City</label>
        <input type="text" name="customer_city"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Fulfillment + Payment --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Fulfillment & Payment</h2>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Fulfillment *</label>
        <select name="fulfillment_type" id="fulfillmentSelect" onchange="toggleDelivery()" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option value="Collection">Pickup / Collection (walk-in)</option>
          <option value="Delivery">Delivery / Shipping</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Payment Method *</label>
        <select name="payment_method" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option>Cash</option><option>Bank Transfer</option><option>Moniepoint</option>
          <option>POS</option><option>Zelle</option><option>CashApp</option>
          <option>Venmo</option><option>Paystack</option><option>Credit / Deferred</option>
        </select>
      </div>
      <div class="col-span-2" id="deliveryAddressBox" style="display:none;">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Delivery Address</label>
        <input type="text" name="delivery_address"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div class="col-span-2">
        <label class="flex items-center gap-2 text-sm font-body text-gray-700">
          <input type="checkbox" name="payment_received" value="1" id="paymentReceivedCheck" class="rounded border-gray-300">
          Payment already received — confirm this order immediately
        </label>
        <p class="text-xs text-gray-400 font-body mt-1">Leave unchecked for phone orders awaiting payment, or pickup orders paying on collection.</p>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes (optional)</label>
        <input type="text" name="notes"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Parts Cart --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-navy">
      <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Search & Add Parts</h2>
      <p class="text-xs text-gray-400 mt-0.5">Only parts genuinely Available with stock will appear</p>
    </div>
    <div class="p-5 border-b border-gray-100">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <select id="searchLocationSelect" onchange="searchParts()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option value="">All Locations</option>
          @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
        </select>
        <input type="text" id="searchQueryInput" placeholder="Search part name, code, brand, model..." oninput="searchParts()"
          class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div id="searchResults" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto"></div>
    </div>

    <div id="cartContainer" class="divide-y divide-gray-100 p-4 space-y-2">
      <p class="text-xs text-gray-400 font-body text-center py-4" id="emptyCartMsg">No parts added yet — search above.</p>
    </div>

    <div class="flex justify-end px-5 py-4 bg-gray-50 border-t border-gray-200">
      <div class="w-72">
        <div class="flex justify-between text-sm font-body py-1 border-t border-gray-200 mt-1 pt-2">
          <span class="font-display font-700 text-navy uppercase tracking-wide">TOTAL:</span>
          <span id="cartTotalDisplay" class="font-display font-800 text-navy text-lg">—</span>
        </div>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.orders.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Place Order
    </button>
  </div>

</div>
</form>

<script>
function toggleDelivery() {
    document.getElementById('deliveryAddressBox').style.display =
        document.getElementById('fulfillmentSelect').value === 'Delivery' ? '' : 'none';
}

// ── Cart state ──────────────────────────────────────────────────────────
let cart = []; // [{part_id, part_code, part_name, brand, model, location, price_local, currency_code, symbol, qty, stock_qty}]

const SYMBOLS = { NGN: '₦', GHS: 'GH₵', USD: '$' };

let searchTimer = null;
function searchParts() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(doSearch, 300);
}

async function doSearch() {
    const q = document.getElementById('searchQueryInput').value;
    const loc = document.getElementById('searchLocationSelect').value;
    const box = document.getElementById('searchResults');

    if (!q && !loc) { box.innerHTML = ''; return; }

    box.innerHTML = '<div class="text-xs text-gray-400 col-span-2">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.orders.place.search-parts') }}?q=${encodeURIComponent(q)}&location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        if (!data.parts || data.parts.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 col-span-2">No matching parts in stock.</div>';
            return;
        }
        box.innerHTML = data.parts.map(p => {
            const priceLocal = p.price_local ?? p.price_usd;
            const sym = SYMBOLS[p.currency_code] || '$';
            const priceFmt = p.currency_code === 'NGN' ? Math.round(priceLocal).toLocaleString() : Number(priceLocal).toFixed(2);
            return `
            <div onclick='addToCart(${JSON.stringify(p).replace(/'/g, "&#39;")})'
                class="border border-gray-200 rounded-lg p-2.5 cursor-pointer hover:border-gold transition-colors">
                <div class="font-700 text-navy text-xs">${p.part_name}</div>
                <div class="text-xs text-gray-400">${p.brand} ${p.model} · ${p.part_code} · ${p.location}</div>
                <div class="text-xs text-gray-500 mt-1">Grade ${p.condition_grade} · Stock: ${p.stock_qty} · <strong class="text-navy">${sym}${priceFmt}</strong></div>
            </div>`;
        }).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 col-span-2">Search failed.</div>';
    }
}

function addToCart(part) {
    const existing = cart.find(c => c.part_id === part.id);
    if (existing) {
        if (existing.qty < part.stock_qty) existing.qty++;
        else alert(`Only ${part.stock_qty} in stock for ${part.part_code}.`);
    } else {
        cart.push({
            part_id: part.id, part_code: part.part_code, part_name: part.part_name,
            brand: part.brand, model: part.model, location: part.location,
            price_local: part.price_local ?? part.price_usd, currency_code: part.currency_code || 'USD',
            qty: 1, stock_qty: part.stock_qty,
        });
    }
    renderCart();
}

function removeFromCart(partId) {
    cart = cart.filter(c => c.part_id !== partId);
    renderCart();
}

function changeQty(partId, delta) {
    const item = cart.find(c => c.part_id === partId);
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty < 1) { removeFromCart(partId); return; }
    if (newQty > item.stock_qty) { alert(`Only ${item.stock_qty} in stock.`); return; }
    item.qty = newQty;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const emptyMsg = document.getElementById('emptyCartMsg');

    if (cart.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-400 font-body text-center py-4" id="emptyCartMsg">No parts added yet — search above.</p>';
        document.getElementById('cartTotalDisplay').textContent = '—';
        return;
    }

    let totalsByCode = {};
    container.innerHTML = cart.map((item, idx) => {
        const sym = SYMBOLS[item.currency_code] || '$';
        const lineTotal = item.price_local * item.qty;
        totalsByCode[item.currency_code] = (totalsByCode[item.currency_code] || 0) + lineTotal;
        const priceFmt = item.currency_code === 'NGN' ? Math.round(lineTotal).toLocaleString() : lineTotal.toFixed(2);

        return `
        <div class="flex items-center justify-between py-2.5">
            <div class="flex-1">
                <div class="font-700 text-navy text-sm">${item.part_name}</div>
                <div class="text-xs text-gray-400">${item.part_code} · ${item.brand} ${item.model} · ${item.location}</div>
                <input type="hidden" name="items[${idx}][part_id]" value="${item.part_id}">
                <input type="hidden" name="items[${idx}][qty]" value="${item.qty}">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="changeQty(${item.part_id}, -1)" class="w-6 h-6 border border-gray-200 rounded text-xs hover:bg-gray-100">−</button>
                <span class="text-sm font-mono w-6 text-center">${item.qty}</span>
                <button type="button" onclick="changeQty(${item.part_id}, 1)" class="w-6 h-6 border border-gray-200 rounded text-xs hover:bg-gray-100">+</button>
                <span class="font-display font-700 text-navy text-sm w-24 text-right">${sym}${priceFmt}</span>
                <button type="button" onclick="removeFromCart(${item.part_id})" class="text-red-400 hover:text-red-600 text-xs ml-2">✕</button>
            </div>
        </div>`;
    }).join('');

    const totalDisplay = Object.entries(totalsByCode).map(([code, amt]) => {
        const sym = SYMBOLS[code] || '$';
        return sym + (code === 'NGN' ? Math.round(amt).toLocaleString() : amt.toFixed(2));
    }).join(' + ');
    document.getElementById('cartTotalDisplay').textContent = totalDisplay;
}

document.getElementById('placeOrderForm').addEventListener('submit', function(e) {
    if (cart.length === 0) {
        e.preventDefault();
        alert('Add at least one part to the cart before placing the order.');
    }
});

// ── Customer lookup (same pattern as manual invoice / quick receipt) ───
let customerLookupTimer = null;
function lookupCustomer(q) {
    clearTimeout(customerLookupTimer);
    const box = document.getElementById('customerSuggestions');
    if (!q || q.length < 2) { box.classList.add('hidden'); return; }
    customerLookupTimer = setTimeout(() => fetchCustomers(q), 300);
}
async function fetchCustomers(q) {
    const box = document.getElementById('customerSuggestions');
    box.classList.remove('hidden');
    box.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.customers.lookup') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.customers || data.customers.length === 0) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400">No matching customer.</div>';
            return;
        }
        box.innerHTML = data.customers.map(c => `
            <div onclick='selectCustomer(${JSON.stringify(c).replace(/'/g, "&#39;")})' class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100">
                <div class="font-700 text-navy text-xs">${c.name || 'Unnamed'}</div>
                <div class="text-xs text-gray-400">${c.phone} · ${c.order_count} order${c.order_count !== 1 ? 's' : ''}</div>
            </div>`).join('');
    } catch (e) { box.innerHTML = '<div class="px-3 py-2 text-xs text-red-500">Search failed.</div>'; }
}
function selectCustomer(c) {
    document.getElementById('customerPhoneInput').value = c.phone || '';
    document.getElementById('customerNameInput').value  = c.name || '';
    document.getElementById('customerEmailInput').value = c.email || '';
    document.getElementById('customerSuggestions').classList.add('hidden');
}
document.addEventListener('click', function(e) {
    const box = document.getElementById('customerSuggestions');
    if (box && !box.contains(e.target) && e.target.id !== 'customerPhoneInput') box.classList.add('hidden');
});
</script>
@endsection
