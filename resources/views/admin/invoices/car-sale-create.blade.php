{{-- FILE: resources/views/admin/invoices/car-sale-create.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Complete Car Sale')
@section('page-title', 'Complete Car Sale Receipt')
@section('page-sub', 'For whole-vehicle sales — sold as-is, no warranty implied or expressed')

@section('content')
<form method="POST" action="{{ route('admin.invoices.car-sale.store') }}" id="carSaleForm">
@csrf

<div class="max-w-5xl space-y-5">

  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs font-body text-amber-700">
    ⚠ This receipt type is for complete vehicle sales only. It is sold <strong>AS-IS, with no warranty implied or expressed</strong> — this disclaimer prints automatically on every copy. For used parts, use "New Invoice" instead, which carries the normal parts warranty.
  </div>

  {{-- Customer Info --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Buyer Information</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Buyer Name *</label>
        <input type="text" name="customer_name" required placeholder="Full name"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone</label>
        <input type="text" name="customer_phone" placeholder="+1 512 000 0000"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email (optional)</label>
        <input type="email" name="customer_email" placeholder="buyer@email.com"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
      <div class="col-span-2 sm:col-span-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Address (optional)</label>
        <input type="text" name="customer_address" placeholder="Street, city, etc."
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
        <select name="payment_method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
          <option>Cash</option>
          <option>Bank Transfer</option>
          <option>Zelle</option>
          <option>CashApp</option>
          <option>Venmo</option>
          <option>Credit / Deferred</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes (optional)</label>
        <input type="text" name="notes" placeholder="Any special notes for this sale..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- Vehicles --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 bg-navy">
      <div>
        <h2 class="font-display font-700 text-white text-sm uppercase tracking-wide">Vehicle(s)</h2>
        <p class="text-xs text-gray-400 mt-0.5">Usually one vehicle — add more only if selling multiple on the same receipt</p>
      </div>
      <div class="flex items-center gap-2">
        <span id="currencyDisplay" class="text-gold font-mono font-700 text-sm">USD ($)</span>
        <button type="button" onclick="addVehicle()"
          class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Vehicle
        </button>
      </div>
    </div>

    <div id="vehiclesContainer" class="divide-y divide-gray-100 p-4 space-y-3">
      {{-- Vehicles added dynamically --}}
    </div>

    <div class="flex justify-end px-5 py-4 bg-gray-50 border-t border-gray-200">
      <div class="w-72">
        <div class="flex justify-between text-sm font-body py-1 border-t border-gray-200 pt-2">
          <span class="font-display font-700 text-navy uppercase tracking-wide">TOTAL:</span>
          <span id="totalDisplay" class="font-display font-800 text-navy text-lg">$0.00</span>
        </div>
      </div>
    </div>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.invoices.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg flex items-center gap-2">
      Generate Car Sale Receipt
    </button>
  </div>

</div>
</form>

<script>
const BRANDS = {!! json_encode($brands) !!};
const YEARS  = {!! json_encode($years) !!};

const CURRENCIES = {
    'Waxahachie TX': { code: 'USD', symbol: '$' },
    'Kennedale TX':  { code: 'USD', symbol: '$' },
    'Elkhorn WI':    { code: 'USD', symbol: '$' },
    'Ile-Ife Nigeria':{ code: 'NGN', symbol: '₦' },
    'Ibadan Nigeria':{ code: 'NGN', symbol: '₦' },
    'Lagos Nigeria': { code: 'NGN', symbol: '₦' },
    'Abuja Nigeria': { code: 'NGN', symbol: '₦' },
    'Akure Nigeria': { code: 'NGN', symbol: '₦' },
    'Accra Ghana':   { code: 'GHS', symbol: 'GH₵' },
};

let vehicleCount = 0;
let currency = { code: 'USD', symbol: '$' };

function updateCurrency() {
    const loc = document.getElementById('locationSelect').value;
    currency = CURRENCIES[loc] || { code: 'USD', symbol: '$' };
    document.getElementById('currencyDisplay').textContent = currency.code + ' (' + currency.symbol + ')';
    updateTotal();
}

function addVehicle() {
    vehicleCount++;
    const i = vehicleCount;
    const container = document.getElementById('vehiclesContainer');
    const row = document.createElement('div');
    row.id = 'vehicle-row-' + i;
    row.className = 'bg-gray-50 rounded-xl p-3 border border-gray-200';
    row.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-display font-700 text-navy uppercase">Vehicle #${i}</span>
            <button type="button" onclick="removeVehicle(${i})" class="text-red-400 hover:text-red-600 text-xs">✕ Remove</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Brand *</label>
                <select name="vehicles[${i}][brand]" required class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm bg-white focus:outline-none focus:border-gold">
                    ${BRANDS.map(b => `<option value="${b}">${b}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Model *</label>
                <input type="text" name="vehicles[${i}][model]" required placeholder="e.g. Camry"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Year *</label>
                <select name="vehicles[${i}][year]" required class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm bg-white focus:outline-none focus:border-gold">
                    ${YEARS.map(y => `<option value="${y}">${y}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Colour</label>
                <input type="text" name="vehicles[${i}][colour]" placeholder="e.g. Silver"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="block text-xs text-gray-400 mb-1">VIN (optional)</label>
                <input type="text" name="vehicles[${i}][vin]" maxlength="17" placeholder="17-character VIN"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm font-mono uppercase focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Mileage</label>
                <input type="number" name="vehicles[${i}][mileage]" min="0" placeholder="e.g. 85000"
                    class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-gold">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1 price-label">Sale Price (${currency.code}) *</label>
                <input type="number" name="vehicles[${i}][price]" id="vehicle-price-${i}" step="0.01" min="0" required
                    oninput="updateTotal()"
                    class="price-input w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm font-mono focus:outline-none focus:border-gold">
            </div>
        </div>
    `;
    container.appendChild(row);
}

function removeVehicle(i) {
    document.getElementById('vehicle-row-' + i)?.remove();
    updateTotal();
}

function updateTotal() {
    let total = 0;
    for (let i = 1; i <= vehicleCount; i++) {
        const priceEl = document.getElementById('vehicle-price-' + i);
        if (priceEl) total += parseFloat(priceEl.value || 0);
    }
    const fmtTotal = currency.symbol + (currency.code === 'NGN' ? Math.round(total).toLocaleString() : total.toFixed(2));
    document.getElementById('totalDisplay').textContent = fmtTotal;
}

updateCurrency();
addVehicle();
</script>
@endsection
