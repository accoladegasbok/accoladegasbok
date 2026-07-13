{{-- FILE: resources/views/admin/invoices/manual.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Create Invoice')
@section('page-title', 'Create Invoice')
@section('page-sub', 'Issue an invoice for walk-in or phone customers')

@section('content')
<form method="POST" action="{{ route('admin.invoices.manual.store') }}" id="invoiceForm">
@csrf

<div class="max-w-5xl space-y-5">

  {{-- Customer Info --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Customer Information</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="col-span-2 sm:col-span-1 relative">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone Number <span class="text-gray-400 font-normal">(search existing customers)</span></label>
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
      <div class="col-span-2 sm:col-span-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Address (optional)</label>
        <input type="text" name="customer_address" id="customerAddressInput" placeholder="Street, city, etc."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
    <div id="customerHistoryNote" class="hidden mt-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 text-xs font-body text-blue-700"></div>
  </div>

  {{-- Sale Details --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Sale Details</h2>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Warehouse Location *</label>
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
          <option>Cash</option>
          <option>Bank Transfer</option>
          <option>Moniepoint</option>
          <option>POS</option>
          <option>Zelle</option>
          <option>CashApp</option>
          <option>Venmo</option>
          <option>Paystack</option>
          <option>Credit / Deferred</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes (optional)</label>
        <input type="text" name="notes" placeholder="Any special notes for this invoice..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Line Items --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 bg-navy">
      <div>
        <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Parts / Items</h2>
        <p class="text-xs text-gray-400 mt-0.5">Select from inventory or enter manually</p>
      </div>
      <div class="flex items-center gap-2">
        <span id="currencyDisplay" class="text-gold font-mono font-700 text-sm">USD ($)</span>
        <select id="serviceRatePicker" onchange="addServiceFromPicker(this)" class="border border-blue-200 rounded-xl text-xs px-2 py-2 bg-blue-50 text-blue-700 focus:outline-none">
          <option value="">⚙ + Add Service Rate...</option>
          @foreach($serviceRates as $sr)
          <option value="{{ $sr->id }}" data-name="{{ $sr->name }}"
                  data-prices="{{ json_encode(($servicePricesByLocation[$sr->id] ?? collect())->toArray()) }}"
                  data-default-price="{{ $sr->default_price ?? 0 }}">{{ $sr->name }}{{ $sr->category ? ' ('.$sr->category.')' : '' }}</option>
          @endforeach
        </select>
        <button type="button" onclick="addItem()"
          class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Item
        </button>
      </div>
    </div>

    <div id="itemsContainer" class="divide-y divide-gray-100 p-4 space-y-3">
      {{-- Items added dynamically --}}
    </div>

    {{-- Totals --}}
    <div class="flex justify-end px-5 py-4 bg-gray-50 border-t border-gray-200">
      <div class="w-72">
        <div class="flex justify-between items-center text-sm font-body py-1">
          <span class="text-gray-500">Subtotal:</span>
          <span id="subtotalDisplay" class="font-700 text-navy">$0.00</span>
        </div>
        <div class="flex justify-between items-center text-sm font-body py-1">
          <span class="text-gray-500">Invoice Discount:</span>
          <div class="flex gap-1 items-center">
            <input type="number" name="invoice_discount_value" id="invoiceDiscountInput"
                placeholder="0" min="0" oninput="updateTotal()"
                class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm font-mono text-right focus:outline-none focus:border-gold">
            <select name="invoice_discount_type" id="invoiceDiscountType" onchange="updateTotal()"
                class="border border-gray-200 rounded-lg px-1 py-1 text-xs bg-white focus:outline-none">
                <option value="fixed">$</option>
                <option value="percent">%</option>
            </select>
          </div>
        </div>
        <div class="flex justify-between text-sm font-body py-1 border-t border-gray-200 mt-1 pt-2">
          <span class="font-display font-700 text-navy uppercase tracking-wide">TOTAL:</span>
          <span id="totalDisplay" class="font-display font-800 text-navy text-lg">$0.00</span>
        </div>
        <div id="capWarning" class="hidden mt-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-3 py-2 rounded-lg"></div>
        <div id="overrideReasonBox" class="hidden mt-2">
          <input type="text" name="discount_override_reason" id="overrideReasonInput" placeholder="Reason for exceeding discount cap..."
            class="w-full border border-amber-300 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:border-amber-500">
        </div>
      </div>
    </div>
  </div>

  {{-- ── Legal Trace (Phase 6) ───────────────────────────────────────────
       Shown automatically when a legal-trace part is in the cart.
       The server enforces this — the UI just makes it clear and collects
       the buyer document reference before submission.
  ── --}}
  <div id="legalTraceField" class="hidden">
    <div class="bg-red-50 border border-red-300 rounded-2xl p-5">
      <div class="flex items-start gap-3">
        <span class="text-2xl mt-0.5">⚠</span>
        <div class="flex-1">
          <h3 class="font-display font-700 text-red-700 text-sm uppercase tracking-wide mb-1">
            Legal Trace Documentation Required
          </h3>
          <p class="text-xs text-red-600 mb-3">
            One or more parts in this sale (catalytic converter, airbag, engine, or other major component)
            require buyer documentation before the sale can be completed.
            Enter the buyer's government ID number, vehicle title number, or receipt reference below.
          </p>
          <label class="block text-xs text-red-700 font-700 uppercase tracking-wide mb-1.5">
            Buyer Document Reference *
          </label>
          <input type="text" name="buyer_legal_doc" id="buyerLegalDocInput"
                 placeholder="e.g. DL-NGA-123456789, Title No. TX-2024-0091234, Receipt #88210..."
                 class="w-full border border-red-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-red-500 bg-white">
          <p class="text-[10px] text-red-400 mt-1.5">
            This reference is recorded against the part's inventory record and the invoice for audit purposes.
          </p>
        </div>
      </div>
    </div>
  </div>

  {{-- Submit --}}
  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.invoices.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Generate Invoice
    </button>
  </div>

</div>
</form>

<script>
const PARTS = {!! json_encode($parts) !!};

const CURRENCIES = {
    'Waxahachie TX':  { code: 'USD', symbol: '$'   },
    'Kennedale TX':   { code: 'USD', symbol: '$'   },
    'Elkhorn WI':     { code: 'USD', symbol: '$'   },
    'Ile-Ife Nigeria':{ code: 'NGN', symbol: '₦'  },
    'Ibadan Nigeria': { code: 'NGN', symbol: '₦'  },
    'Lagos Nigeria':  { code: 'NGN', symbol: '₦'  },
    'Abuja Nigeria':  { code: 'NGN', symbol: '₦'  },
    'Akure Nigeria':  { code: 'NGN', symbol: '₦'  },
    'Accra Ghana':    { code: 'GHS', symbol: 'GH₵' },
};

let itemCount = 0;
let currency  = { code: 'USD', symbol: '$' };

// Tracks which items in the cart require legal trace
// key = item index, value = true/false
const legalTraceItems = {};

function updateCurrency() {
    const loc = document.getElementById('locationSelect').value;
    currency  = CURRENCIES[loc] || { code: 'USD', symbol: '$' };
    document.getElementById('currencyDisplay').textContent = currency.code + ' (' + currency.symbol + ')';
    document.querySelectorAll('.price-input').forEach(el => {
        el.placeholder = currency.code === 'NGN' ? '0' : '0.00';
        el.step        = currency.code === 'NGN' ? '1' : '0.01';
    });
    updateTotal();
}

// ── Customer lookup ───────────────────────────────────────────
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
        const res  = await fetch(`{{ route('admin.customers.lookup') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.customers || data.customers.length === 0) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400">No matching customer — keep typing to create a new one.</div>';
            return;
        }
        box.innerHTML = data.customers.map(c => `
            <div onclick='selectCustomer(${JSON.stringify(c).replace(/'/g, "&#39;")})'
                class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100">
                <div class="font-700 text-navy text-xs">${c.name || 'Unnamed'}</div>
                <div class="text-xs text-gray-400">${c.phone} · ${c.order_count} order${c.order_count !== 1 ? 's' : ''}</div>
            </div>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="px-3 py-2 text-xs text-red-500">Search failed.</div>';
    }
}

function selectCustomer(c) {
    document.getElementById('customerPhoneInput').value   = c.phone   || '';
    document.getElementById('customerNameInput').value    = c.name    || '';
    document.getElementById('customerEmailInput').value   = c.email   || '';
    document.getElementById('customerAddressInput').value = c.address || '';
    document.getElementById('customerSuggestions').classList.add('hidden');
    const note = document.getElementById('customerHistoryNote');
    note.textContent = `Returning customer — ${c.order_count} previous order${c.order_count !== 1 ? 's' : ''}.`;
    note.classList.remove('hidden');
}

document.addEventListener('click', function(e) {
    const box = document.getElementById('customerSuggestions');
    if (box && !box.contains(e.target) && e.target.id !== 'customerPhoneInput') box.classList.add('hidden');
});

// ── Legal Trace check ─────────────────────────────────────────
// Called whenever a part is selected or removed from the cart.
// Shows/hides the legal trace documentation field.
function checkLegalTrace() {
    const needsLegal = Object.values(legalTraceItems).some(v => v === true);
    const field      = document.getElementById('legalTraceField');
    const input      = document.getElementById('buyerLegalDocInput');
    field.classList.toggle('hidden', !needsLegal);
    input.required = needsLegal;
}

function addItem() {
    itemCount++;
    const i = itemCount;
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.id        = 'item-row-' + i;
    row.className = 'bg-gray-50 rounded-xl p-3 border border-gray-200';
    row.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-display font-700 text-navy uppercase">Item #${i}</span>
            <button type="button" onclick="removeItem(${i})" class="text-red-400 hover:text-red-600 text-xs">✕ Remove</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
            <div class="sm:col-span-2 relative">
                <label class="block text-xs text-gray-400 mb-1">Part Name * <span class="font-normal">(browse or type)</span></label>
                <input type="text" name="items[${i}][name]" id="item-name-${i}"
                    placeholder="Click to browse inventory or type a new part..."
                    autocomplete="off"
                    onfocus="showAllPartsForLocation(${i})"
                    oninput="searchParts(${i}, this.value)"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
                <div id="suggestions-${i}" class="absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-72 hidden max-h-48 overflow-y-auto"></div>
                <input type="hidden" name="items[${i}][part_id]" id="item-pid-${i}">
                <div id="item-legal-badge-${i}" class="hidden mt-1">
                    <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-600 font-700">⚠ Legal Trace Required</span>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Grade</label>
                <select name="items[${i}][grade]" id="item-grade-${i}"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm bg-white focus:outline-none focus:border-gold">
                    <option value="A">A — Like New</option>
                    <option value="B" selected>B — Good</option>
                    <option value="C">C — Fair</option>
                    <option value="New">New</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Qty</label>
                <input type="number" name="items[${i}][qty]" id="item-qty-${i}"
                    value="1" min="1" oninput="updateTotal()"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1 price-label">Unit Price *</label>
                <input type="number" name="items[${i}][price]" id="item-price-${i}"
                    placeholder="0" step="1" min="0" oninput="updateTotal()"
                    class="price-input w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm font-mono focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Discount</label>
                <div class="flex gap-1">
                    <input type="number" name="items[${i}][discount_value]" id="item-discount-${i}"
                        placeholder="0" min="0" oninput="updateTotal()"
                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm font-mono focus:outline-none focus:border-gold">
                    <select name="items[${i}][discount_type]" id="item-discount-type-${i}" onchange="updateTotal()"
                        class="border border-gray-200 rounded-lg px-1 py-1.5 text-xs bg-white focus:outline-none">
                        <option value="fixed">$</option>
                        <option value="percent">%</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-2">
            <div class="text-right">
                <div class="text-xs text-gray-400">Line Total</div>
                <div id="item-total-${i}" class="font-display font-700 text-navy text-base">—</div>
            </div>
        </div>
    `;
    container.appendChild(row);
    legalTraceItems[i] = false;
    return i;
}

function addServiceFromPicker(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    const i = addItem();
    document.getElementById('item-name-' + i).value = opt.dataset.name;
    document.getElementById('item-pid-' + i).value  = '';
    const loc = document.getElementById('locationSelect')?.value;
    let prices = {};
    try { prices = JSON.parse(opt.dataset.prices || '{}'); } catch (e) {}
    const priceToUse = prices[loc] !== undefined ? prices[loc] : opt.dataset.defaultPrice;
    const priceInput = document.getElementById('item-price-' + i);
    if (priceInput && priceToUse) priceInput.value = priceToUse;
    sel.value = '';
    updateTotal();
}

function removeItem(i) {
    document.getElementById('item-row-' + i)?.remove();
    delete legalTraceItems[i];
    checkLegalTrace();
    updateTotal();
}

function getLocationParts() {
    const loc = document.getElementById('locationSelect').value;
    return PARTS.filter(p => (p.location || '') === loc);
}

function showAllPartsForLocation(i) {
    renderSuggestions(i, document.getElementById('suggestions-' + i), getLocationParts().slice(0, 50));
}

function searchParts(i, query) {
    const box  = document.getElementById('suggestions-' + i);
    const pool = getLocationParts();
    if (!query || query.length < 1) { renderSuggestions(i, box, pool.slice(0, 50)); return; }
    const q = query.toLowerCase();
    const matches = pool.filter(p =>
        (p.part_name || '').toLowerCase().includes(q) ||
        (p.part_code || '').toLowerCase().includes(q) ||
        (p.brand || '').toLowerCase().includes(q) ||
        (p.model || '').toLowerCase().includes(q) ||
        String(p.year_from || '').includes(q) ||
        String(p.year_to   || '').includes(q)
    ).slice(0, 50);
    renderSuggestions(i, box, matches);
}

function renderSuggestions(i, box, matches) {
    if (matches.length === 0) {
        box.innerHTML = '<div class="px-3 py-3 text-xs text-gray-400">No matching parts — type a name to add manually.</div>';
        box.classList.remove('hidden'); return;
    }
    box.innerHTML = matches.map(p => {
        const yr = p.year_from && p.year_to ? (p.year_from === p.year_to ? p.year_from : `${p.year_from}–${p.year_to}`) : '';
        const priceLocal = p.price_local ?? p.price_usd;
        const priceFmt   = currency.code === 'NGN' ? Math.round(priceLocal).toLocaleString() : Number(priceLocal).toFixed(2);
        return `<div onclick="selectPart(${i}, ${JSON.stringify(p).replace(/"/g, '&quot;')})"
            class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100">
            <div class="font-700 text-navy text-xs">${p.part_name}${p.legal_trace_required ? ' <span class="text-red-500">⚠</span>' : ''}</div>
            <div class="text-xs text-gray-400">${p.brand} ${p.model} ${yr} · ${p.part_code} · Grade ${p.condition_grade} · ${currency.symbol}${priceFmt}</div>
        </div>`;
    }).join('') + `<div class="px-3 py-2 text-xs text-gray-400 bg-gray-50 italic">Not listed? Type the part name to add manually.</div>`;
    box.classList.remove('hidden');
}

function selectPart(i, part) {
    document.getElementById('item-name-' + i).value  = part.part_name;
    document.getElementById('item-pid-' + i).value   = part.id;
    const priceLocal = part.price_local ?? part.price_usd;
    document.getElementById('item-price-' + i).value = currency.code === 'NGN' ? Math.round(priceLocal) : Number(priceLocal).toFixed(2);
    document.getElementById('item-grade-' + i).value = part.condition_grade || 'B';
    document.getElementById('suggestions-' + i).classList.add('hidden');

    // Legal trace check for this item
    const needsLegal = !!(part.legal_trace_required);
    legalTraceItems[i] = needsLegal;
    const badge = document.getElementById('item-legal-badge-' + i);
    if (badge) badge.classList.toggle('hidden', !needsLegal);
    checkLegalTrace();

    updateTotal();
}

const STAFF_DISCOUNT_CAP_FIXED   = {{ $staffDiscountCapFixed   ?? 'null' }};
const STAFF_DISCOUNT_CAP_PERCENT = {{ $staffDiscountCapPercent ?? 'null' }};

function applyDiscount(amount, value, type) {
    value = parseFloat(value || 0);
    if (value <= 0) return { discounted: amount, discountAmt: 0 };
    if (type === 'percent') {
        const discountAmt = amount * (value / 100);
        return { discounted: amount - discountAmt, discountAmt };
    }
    const discountAmt = Math.min(value, amount);
    return { discounted: amount - discountAmt, discountAmt };
}

function updateTotal() {
    let subtotal = 0, totalLineDiscountLocal = 0;
    for (let i = 1; i <= itemCount; i++) {
        const priceEl    = document.getElementById('item-price-' + i);
        const qtyEl      = document.getElementById('item-qty-' + i);
        const totalEl    = document.getElementById('item-total-' + i);
        const discValEl  = document.getElementById('item-discount-' + i);
        const discTypeEl = document.getElementById('item-discount-type-' + i);
        if (!priceEl) continue;
        const price      = parseFloat(priceEl.value || 0);
        const qty        = parseInt(qtyEl?.value || 1);
        const lineGross  = price * qty;
        const { discounted: lineNet, discountAmt } = applyDiscount(lineGross, discValEl?.value, discTypeEl?.value || 'fixed');
        subtotal               += lineNet;
        totalLineDiscountLocal += discountAmt;
        if (totalEl) totalEl.textContent = lineNet > 0
            ? currency.symbol + (currency.code === 'NGN' ? Math.round(lineNet).toLocaleString() : lineNet.toFixed(2))
            : '—';
    }

    const invDiscVal  = document.getElementById('invoiceDiscountInput')?.value;
    const invDiscType = document.getElementById('invoiceDiscountType')?.value || 'fixed';
    const { discounted: total, discountAmt: invoiceDiscountLocal } = applyDiscount(subtotal, invDiscVal, invDiscType);
    const totalDiscountLocal = totalLineDiscountLocal + invoiceDiscountLocal;
    const grossLocal = subtotal + totalLineDiscountLocal;
    const discountPct        = grossLocal > 0 ? (totalDiscountLocal / grossLocal) * 100 : 0;

    document.getElementById('subtotalDisplay').textContent = currency.symbol + (currency.code === 'NGN' ? Math.round(grossLocal).toLocaleString() : grossLocal.toFixed(2));
    document.getElementById('totalDisplay').textContent    = currency.symbol + (currency.code === 'NGN' ? Math.round(total).toLocaleString()     : total.toFixed(2));

    checkDiscountCap(totalDiscountLocal, discountPct);
}

function checkDiscountCap(discountLocal, discountPercent) {
    const warningEl   = document.getElementById('capWarning');
    const overrideBox = document.getElementById('overrideReasonBox');
    const exceedsFixed   = STAFF_DISCOUNT_CAP_FIXED   !== null && discountLocal   > STAFF_DISCOUNT_CAP_FIXED;
    const exceedsPercent = STAFF_DISCOUNT_CAP_PERCENT !== null && discountPercent > STAFF_DISCOUNT_CAP_PERCENT;
    if (exceedsFixed || exceedsPercent) {
        const parts = [];
        if (exceedsFixed)   parts.push(`fixed cap is ${currency.symbol}${STAFF_DISCOUNT_CAP_FIXED.toFixed(2)}`);
        if (exceedsPercent) parts.push(`percentage cap is ${STAFF_DISCOUNT_CAP_PERCENT}%`);
        warningEl.textContent = 'Discount exceeds your allowance: ' + parts.join(' and ') + '. Provide a reason to proceed.';
        warningEl.classList.remove('hidden');
        overrideBox.classList.remove('hidden');
        document.getElementById('overrideReasonInput').required = true;
    } else {
        warningEl.classList.add('hidden');
        overrideBox.classList.add('hidden');
        document.getElementById('overrideReasonInput').required = false;
        document.getElementById('overrideReasonInput').value    = '';
    }
}

document.addEventListener('click', function(e) {
    document.querySelectorAll('[id^="suggestions-"]').forEach(el => {
        const idx      = el.id.replace('suggestions-', '');
        const ownInput = document.getElementById('item-name-' + idx);
        if (!el.contains(e.target) && e.target !== ownInput) el.classList.add('hidden');
    });
});

updateCurrency();
addItem();
</script>
@endsection
