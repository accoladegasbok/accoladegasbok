{{-- FILE: resources/views/admin/invoices/service.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Quick Receipt')
@section('page-title', 'Quick Receipt — Service / Misc')
@section('page-sub', 'For labor, diagnostics, or miscellaneous charges — never touches parts inventory')

@section('header-actions')
<a href="{{ route('admin.service-rates.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  Manage Fixed Rates
</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.invoices.service.store') }}" id="serviceForm">
@csrf

<div class="max-w-4xl space-y-5">

  @if($errors->any())
  <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
  @endif

  {{-- Customer Info --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Customer Information</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="col-span-2 sm:col-span-1 relative">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone Number</label>
        <input type="text" name="customer_phone" id="customerPhoneInput" placeholder="+1 512 000 0000" autocomplete="off"
          oninput="lookupCustomer(this.value)"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        <div id="customerSuggestions" class="absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-full hidden max-h-56 overflow-y-auto"></div>
      </div>
      <div class="col-span-2 sm:col-span-1">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Customer Name *</label>
        <input type="text" name="customer_name" id="customerNameInput" required placeholder="Full name or company"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email (optional)</label>
        <input type="email" name="customer_email" id="customerEmailInput" placeholder="customer@email.com"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Sale Details --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Sale Details</h2>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Location *</label>
        <select name="location" id="locationSelect" onchange="updateCurrency()" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          @foreach($locations as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Payment Method</label>
        <select name="payment_method"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option>Cash</option><option>Bank Transfer</option><option>Moniepoint</option>
          <option>POS</option><option>Zelle</option><option>CashApp</option>
          <option>Venmo</option><option>Paystack</option><option>Credit / Deferred</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes (optional)</label>
        <input type="text" name="notes" placeholder="e.g. Vehicle details, work performed..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Service Items --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 bg-navy">
      <div>
        <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Service / Labor / Misc Items</h2>
        <p class="text-xs text-gray-400 mt-0.5">Pick a fixed-rate service or type a custom charge</p>
      </div>
      <div class="flex items-center gap-2">
        <span id="currencyDisplay" class="text-gold font-mono font-700 text-sm">USD ($)</span>
        <button type="button" onclick="addItem()"
          class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors flex items-center gap-1">
          + Add Item
        </button>
      </div>
    </div>

    <div id="itemsContainer" class="divide-y divide-gray-100 p-4 space-y-3"></div>

    <div class="flex justify-end px-5 py-4 bg-gray-50 border-t border-gray-200">
      <div class="w-72">
        <div class="flex justify-between text-sm font-body py-1 border-t border-gray-200 mt-1 pt-2">
          <span class="font-display font-700 text-navy uppercase tracking-wide">TOTAL:</span>
          <span id="totalDisplay" class="font-display font-800 text-navy text-lg">$0.00</span>
        </div>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.invoices.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Generate Receipt
    </button>
  </div>

</div>
</form>

<script>
const SERVICE_RATES = {!! json_encode($serviceRates) !!};
const CURRENCIES = {
    'Waxahachie TX': { code: 'USD', symbol: '$' },
    'Kennedale TX':  { code: 'USD', symbol: '$' },
    'Elkhorn WI':    { code: 'USD', symbol: '$' },
    'Ile-Ife Nigeria':{ code: 'NGN', symbol: '₦' },
    'Ibadan Nigeria':{ code: 'NGN', symbol: '₦' },
    'Oshodi Lagos':  { code: 'NGN', symbol: '₦' },
    'Accra Ghana':   { code: 'GHS', symbol: 'GH₵' },
};

let itemCount = 0;
let currency = { code: 'USD', symbol: '$' };

function updateCurrency() {
    const loc = document.getElementById('locationSelect').value;
    currency = CURRENCIES[loc] || { code: 'USD', symbol: '$' };
    document.getElementById('currencyDisplay').textContent = currency.code + ' (' + currency.symbol + ')';
    document.querySelectorAll('.price-input').forEach(el => {
        el.placeholder = currency.code === 'NGN' ? '0' : '0.00';
        el.step = currency.code === 'NGN' ? '1' : '0.01';
    });
    updateTotal();
}

function addItem() {
    itemCount++;
    const i = itemCount;
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.id = 'item-row-' + i;
    row.className = 'bg-gray-50 rounded-xl p-3 border border-gray-200';
    const options = SERVICE_RATES.map(s => `<option value="${s.id}" data-price="${s.default_price ?? ''}">${s.name}${s.category ? ' ('+s.category+')' : ''}</option>`).join('');
    row.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-display font-700 text-navy uppercase">Item #${i}</span>
            <button type="button" onclick="removeItem(${i})" class="text-red-400 hover:text-red-600 text-xs">✕ Remove</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-400 mb-1">Fixed-Rate Service (optional)</label>
                <select onchange="applyPreset(${i}, this)" class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm bg-white focus:outline-none focus:border-gold">
                    <option value="">— Custom / Miscellaneous —</option>
                    ${options}
                </select>
                <input type="text" name="items[${i}][name]" id="item-name-${i}" required placeholder="Description (e.g. Brake Pad Replacement — Labor)"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm mt-1.5 focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Qty</label>
                <input type="number" name="items[${i}][qty]" id="item-qty-${i}" value="1" min="1" oninput="updateTotal()"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Price (${currency.code})</label>
                <input type="number" name="items[${i}][price]" id="item-price-${i}" required min="0"
                    placeholder="${currency.code === 'NGN' ? '0' : '0.00'}" step="${currency.code === 'NGN' ? '1' : '0.01'}"
                    oninput="updateTotal()"
                    class="price-input w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm font-mono focus:outline-none focus:border-gold">
            </div>
        </div>
        <div class="text-right mt-2">
            <span class="text-xs text-gray-400">Line Total: </span>
            <span id="item-total-${i}" class="font-display font-700 text-navy">—</span>
        </div>
    `;
    container.appendChild(row);
}

function applyPreset(i, sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('item-name-' + i).value = opt.textContent.replace(/\s*\(.*\)$/, '');
    if (opt.dataset.price) {
        document.getElementById('item-price-' + i).value = opt.dataset.price;
    }
    updateTotal();
}

function removeItem(i) {
    document.getElementById('item-row-' + i)?.remove();
    updateTotal();
}

function updateTotal() {
    let total = 0;
    for (let i = 1; i <= itemCount; i++) {
        const priceEl = document.getElementById('item-price-' + i);
        const qtyEl = document.getElementById('item-qty-' + i);
        const totalEl = document.getElementById('item-total-' + i);
        if (!priceEl) continue;
        const price = parseFloat(priceEl.value || 0);
        const qty = parseInt(qtyEl?.value || 1);
        const lineTotal = price * qty;
        total += lineTotal;
        if (totalEl) totalEl.textContent = lineTotal > 0
            ? currency.symbol + (currency.code === 'NGN' ? Math.round(lineTotal).toLocaleString() : lineTotal.toFixed(2))
            : '—';
    }
    document.getElementById('totalDisplay').textContent = currency.symbol + (currency.code === 'NGN' ? Math.round(total).toLocaleString() : total.toFixed(2));
}

// ── Customer lookup (same pattern as manual invoice form) ──────────────
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
                <div class="text-xs text-gray-400">${c.phone}</div>
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

updateCurrency();
addItem();
</script>
@endsection
