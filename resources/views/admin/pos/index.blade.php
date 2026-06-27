{{-- FILE: resources/views/admin/pos/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','POS Checkout')
@section('page-title','POS Checkout')
@section('page-sub','Scan items to build the cart, then complete the sale')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- ── LEFT (2 cols): Scanner input + cart ──────────────────────── --}}
  <div class="lg:col-span-2 space-y-4">

    {{-- Location + scan input --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
        <div class="sm:col-span-1">
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Location *</label>
          <select id="locationSelect" required
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
            <option value="">Select location</option>
            @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Scan or Type Barcode</label>
          <input type="text" id="scanInput" autocomplete="off" placeholder="Scan part barcode here..." disabled
            class="w-full border-2 border-gold rounded-lg px-4 py-2.5 text-base font-mono focus:outline-none focus:ring-2 focus:ring-gold">
        </div>
      </div>
      <div id="scanFeedback" class="text-sm font-body min-h-[20px]"></div>
    </div>

    {{-- Cart --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3 bg-navy">
        <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Cart</h2>
      </div>
      <div id="cartContainer" class="divide-y divide-gray-100 max-h-[50vh] overflow-y-auto">
        <p class="text-sm text-gray-400 font-body text-center py-10" id="emptyCartMsg">Scan an item to begin.</p>
      </div>
    </div>

  </div>

  {{-- ── RIGHT (1 col): Totals + checkout ─────────────────────────── --}}
  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sticky top-4">
      <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Sale Total</h2>
      <div id="totalDisplay" class="font-display font-800 text-navy text-3xl mb-4">—</div>

      <div class="mb-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Customer Name (optional)</label>
        <input type="text" id="customerName" placeholder="Walk-in Customer"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div class="mb-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone (optional)</label>
        <input type="text" id="customerPhone"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div class="mb-4">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Payment Method *</label>
        <select id="paymentMethod" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option>Cash</option><option>POS</option><option>Bank Transfer</option>
          <option>Moniepoint</option><option>Zelle</option><option>CashApp</option>
          <option>Venmo</option><option>Paystack</option>
        </select>
      </div>

      <button id="checkoutBtn" onclick="completeSale()" disabled
        class="w-full bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-base py-4 rounded-xl tracking-wide transition-colors shadow-lg disabled:opacity-40 disabled:cursor-not-allowed">
        Complete Sale
      </button>
      <button onclick="clearCart()" class="w-full mt-2 border border-gray-200 text-gray-500 font-body font-500 text-sm py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
        Clear Cart
      </button>

      <div id="checkoutFeedback" class="text-sm font-body mt-3"></div>
    </div>
  </div>

</div>

<script>
const SYMBOLS = { NGN: '₦', GHS: 'GH₵', USD: '$' };
let cart = {}; // part_id -> { ...part, qty }

const locationSelect = document.getElementById('locationSelect');
const scanInput       = document.getElementById('scanInput');

locationSelect.addEventListener('change', function() {
    scanInput.disabled = !this.value;
    if (this.value) scanInput.focus();
});

// ── Scanner gun behavior: types the code then sends Enter automatically.
scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleScan(this.value.trim());
        this.value = '';
    }
});

async function handleScan(code) {
    if (!code) return;
    const loc = locationSelect.value;
    const feedback = document.getElementById('scanFeedback');
    feedback.textContent = 'Looking up...';
    feedback.className = 'text-sm font-body min-h-[20px] text-gray-400';

    try {
        const res = await fetch(`{{ route('admin.pos.lookup') }}?code=${encodeURIComponent(code)}&location=${encodeURIComponent(loc)}`);
        const data = await res.json();

        if (!res.ok || data.error) {
            feedback.textContent = '✕ ' + (data.error || 'Not found.');
            feedback.className = 'text-sm font-body min-h-[20px] text-red-600';
            beep(false);
            return;
        }

        addToCart(data.part);
        feedback.textContent = `✓ Added: ${data.part.part_name} (${data.part.part_code})`;
        feedback.className = 'text-sm font-body min-h-[20px] text-green-600';
        beep(true);
    } catch (e) {
        feedback.textContent = 'Network error — try again.';
        feedback.className = 'text-sm font-body min-h-[20px] text-red-600';
    }
}

function addToCart(part) {
    if (cart[part.id]) {
        if (cart[part.id].qty < part.stock_qty) {
            cart[part.id].qty++;
        } else {
            alert(`Only ${part.stock_qty} in stock for ${part.part_code}.`);
        }
    } else {
        cart[part.id] = { ...part, qty: 1 };
    }
    renderCart();
}

function changeQty(id, delta) {
    const item = cart[id];
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty < 1) { removeItem(id); return; }
    if (newQty > item.stock_qty) { alert(`Only ${item.stock_qty} in stock.`); return; }
    item.qty = newQty;
    renderCart();
}

function removeItem(id) {
    const item = cart[id];
    if (!item) return;
    requestOverride('remove_cart_item_pos', `Remove ${item.part_name} (${item.part_code}) from POS cart`, function(approvedBy) {
        delete cart[id];
        renderCart();
    });
}

function clearCart() {
    if (Object.keys(cart).length > 0 && !confirm('Clear the entire cart?')) return;
    cart = {};
    renderCart();
    document.getElementById('checkoutFeedback').textContent = '';
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const ids = Object.keys(cart);
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (ids.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-400 font-body text-center py-10">Scan an item to begin.</p>';
        document.getElementById('totalDisplay').textContent = '—';
        checkoutBtn.disabled = true;
        return;
    }

    let totalsByCode = {};
    container.innerHTML = ids.map(id => {
        const item = cart[id];
        const sym = SYMBOLS[item.currency_code] || '$';
        const lineTotal = item.price_local * item.qty;
        totalsByCode[item.currency_code] = (totalsByCode[item.currency_code] || 0) + lineTotal;
        const priceFmt = item.currency_code === 'NGN' ? Math.round(lineTotal).toLocaleString() : lineTotal.toFixed(2);

        return `
        <div class="flex items-center justify-between px-5 py-3">
            <div class="flex-1">
                <div class="font-700 text-navy text-sm">${item.part_name}</div>
                <div class="text-xs text-gray-400">${item.part_code} · ${item.brand} ${item.model}</div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="changeQty(${id}, -1)" class="w-7 h-7 border border-gray-200 rounded text-sm hover:bg-gray-100">−</button>
                <span class="text-sm font-mono w-6 text-center">${item.qty}</span>
                <button onclick="changeQty(${id}, 1)" class="w-7 h-7 border border-gray-200 rounded text-sm hover:bg-gray-100">+</button>
                <span class="font-display font-700 text-navy text-sm w-24 text-right">${sym}${priceFmt}</span>
                <button onclick="removeItem(${id})" class="text-red-400 hover:text-red-600 text-sm ml-1">✕</button>
            </div>
        </div>`;
    }).join('');

    document.getElementById('totalDisplay').textContent = Object.entries(totalsByCode).map(([code, amt]) => {
        const sym = SYMBOLS[code] || '$';
        return sym + (code === 'NGN' ? Math.round(amt).toLocaleString() : amt.toFixed(2));
    }).join(' + ');

    checkoutBtn.disabled = false;
}

async function completeSale() {
    const ids = Object.keys(cart);
    if (ids.length === 0) return;

    const btn = document.getElementById('checkoutBtn');
    const feedback = document.getElementById('checkoutFeedback');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const items = ids.map(id => ({ part_id: parseInt(id), qty: cart[id].qty }));

    try {
        const res = await fetch(`{{ route('admin.pos.checkout') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                location: locationSelect.value,
                payment_method: document.getElementById('paymentMethod').value,
                customer_name: document.getElementById('customerName').value,
                customer_phone: document.getElementById('customerPhone').value,
                items: items,
            }),
        });
        const data = await res.json();

        if (data.success) {
            feedback.textContent = `✓ Sale complete — ${data.invoice_no}`;
            feedback.className = 'text-sm font-body mt-3 text-green-600 font-700';
            window.open(data.print_url, '_blank');

            // Reset for next customer
            cart = {};
            renderCart();
            document.getElementById('customerName').value = '';
            document.getElementById('customerPhone').value = '';
            scanInput.focus();
        } else {
            feedback.textContent = '✕ ' + (data.error || 'Checkout failed.');
            feedback.className = 'text-sm font-body mt-3 text-red-600';
        }
    } catch (e) {
        feedback.textContent = 'Network error — please try again.';
        feedback.className = 'text-sm font-body mt-3 text-red-600';
    }

    btn.disabled = false;
    btn.textContent = 'Complete Sale';
}

// Simple audio feedback like a real scanner (optional, silent fallback if blocked)
function beep(success) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.frequency.value = success ? 880 : 220;
        osc.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch (e) {}
}
</script>

<div id="overridePinModal" class="hidden fixed inset-0 z-[70] bg-black bg-opacity-50 items-center justify-center px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
    <h3 class="font-display font-700 text-navy text-lg tracking-wide mb-1">Supervisor Approval Required</h3>
    <p id="overrideContextText" class="text-sm text-gray-500 font-body mb-4"></p>
    <input type="text" id="overridePinInput" inputmode="numeric" maxlength="4" autocomplete="off"
      placeholder="Enter 4-digit PIN" class="w-full border-2 border-gold rounded-lg px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none">
    <div id="overrideError" class="text-xs text-red-600 font-body mt-2 min-h-[16px]"></div>
    <div class="flex gap-2 mt-4">
      <button type="button" onclick="closeOverrideModal()" class="flex-1 border border-gray-200 text-gray-600 font-body font-500 text-sm py-2.5 rounded-xl hover:bg-gray-50">Cancel</button>
      <button type="button" onclick="submitOverridePin()" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-2.5 rounded-xl hover:bg-yellow-500">Approve</button>
    </div>
  </div>
</div>

<script>
let _overrideCallback = null;
let _overrideAction = null;
let _overrideContext = null;

function requestOverride(action, context, callback) {
    _overrideAction = action;
    _overrideContext = context;
    _overrideCallback = callback;
    document.getElementById('overrideContextText').textContent = context;
    document.getElementById('overridePinInput').value = '';
    document.getElementById('overrideError').textContent = '';
    const modal = document.getElementById('overridePinModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('overridePinInput').focus();
}

function closeOverrideModal() {
    const modal = document.getElementById('overridePinModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    _overrideCallback = null;
}

async function submitOverridePin() {
    const pin = document.getElementById('overridePinInput').value;
    const errorBox = document.getElementById('overrideError');
    if (pin.length !== 4) { errorBox.textContent = 'Enter a 4-digit PIN.'; return; }

    try {
        const res = await fetch('/admin/override/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ pin, action: _overrideAction, context: _overrideContext }),
        });
        const data = await res.json();
        if (!res.ok || data.error) {
            errorBox.textContent = data.error || 'Invalid PIN.';
            document.getElementById('overridePinInput').value = '';
            return;
        }
        const cb = _overrideCallback;
        closeOverrideModal();
        if (cb) cb(data.approved_by, data.role);
    } catch (e) {
        errorBox.textContent = 'Network error — try again.';
    }
}

document.getElementById('overridePinInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') submitOverridePin();
});
</script>
@endsection
