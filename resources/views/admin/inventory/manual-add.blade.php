{{-- FILE: resources/views/admin/inventory/manual-add.blade.php --}}
{{-- Manual part entry — no donor VIN required --}}
{{-- For: vendor sourced parts, generic items, new parts, secondary sourced --}}

@extends('admin.layouts.admin')
@section('title', 'Add Part Manually')
@section('page-title', 'Add Part Manually')
@section('page-sub', 'For vendor-sourced, generic, new, or secondary parts — no VIN required')

@section('content')

<form method="POST" action="{{ route('admin.inventory.store') }}" enctype="multipart/form-data">
@csrf

<div class="max-w-4xl space-y-6">

  {{-- ── Part Type Banner ─────────────────────────────────────────── --}}
  <div class="bg-navy rounded-2xl p-4">
    <div class="text-white font-display font-700 text-sm uppercase tracking-wide mb-3">Part Source / Type</div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
      @foreach([
        'used_loose'       => ['Used Loose',        'Harvested part, no donor VIN',         'bg-amber-500'],
        'new_open'         => ['New Open Box',       'New but opened/unsealed',              'bg-blue-500'],
        'secondary'        => ['Secondary Sourced',  'Bought from another recycler/vendor',  'bg-purple-500'],
        'special_order'    => ['Special Order',      'Ordered specifically for customer',    'bg-green-500'],
        'generic'          => ['Generic / Universal','Oils, filters, fluids, accessories',   'bg-teal-500'],
        'new_oem'          => ['New OEM',            'Brand new original manufacturer part', 'bg-gold'],
        'remanufactured'   => ['Remanufactured',     'Rebuilt/reconditioned part',           'bg-orange-500'],
        'core'             => ['Core / Return',      'For rebuild or core exchange only',    'bg-red-500'],
      ] as $val => [$label, $desc, $color])
      <label class="cursor-pointer">
        <input type="radio" name="part_source" value="{{ $val }}"
          class="sr-only peer" {{ $val === 'used_loose' ? 'checked' : '' }}>
        <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
          <div class="text-xs font-display font-700 text-navy">{{ $label }}</div>
          <div class="text-xs text-gray-400 font-body mt-0.5">{{ $desc }}</div>
        </div>
      </label>
      @endforeach
    </div>
  </div>

  {{-- ── Vehicle Fitment ──────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Vehicle Fitment</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1" id="brandLabel">Make / Brand</label>
        <select name="brand" id="brandSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Make</option>
          @foreach(\App\Data\VehicleDatabase::makes() as $make)
            <option value="{{ $make }}">{{ $make }}</option>
          @endforeach
          <option value="UNIVERSAL">UNIVERSAL (fits all)</option>
        </select>
      </div>
      <div id="modelField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Model</label>
        <input type="text" name="model" id="modelInput" list="modelDatalist" placeholder="Select brand first, or type new model"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <datalist id="modelDatalist"></datalist>
      </div>
      <div id="yearFromField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Year From</label>
        <input type="number" name="year_from" id="yearFromInput" placeholder="{{ date('Y') }}" min="1986" max="2027"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div id="yearToField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Year To</label>
        <input type="number" name="year_to" id="yearToInput" placeholder="{{ date('Y') }}" min="1986" max="2027"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div id="unitSizeField" class="hidden">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Size / Volume</label>
        <input type="text" name="unit_size" placeholder="e.g. 5L, 1 Quart, 4-pack"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div id="engineSizeField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Engine Size (L)</label>
        <input type="number" step="0.1" min="0.5" max="8.0" id="engineSizeInput" placeholder="e.g. 2.5"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <p class="text-xs text-gray-400 font-body mt-1">Needed for correct engine/gear code — some models have multiple engine options (e.g. 2.5L vs 3.5L V6).</p>
      </div>
    </div>
    <div class="mt-3" id="compatNoteField" style="display:none;">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatibility Note (optional)</label>
      <input type="text" name="compatibility_note" placeholder="e.g. Suitable for most 4-cylinder Toyota/Honda engines"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    </div>
    <div class="mt-3">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Donor VIN (if available)</label>
      <input type="text" name="donor_vin" placeholder="Optional — leave blank if no donor vehicle"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body font-mono uppercase focus:outline-none focus:border-gold">
    </div>
  </div>

  {{-- ── Part Details ──────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Part Details</h2>
    <div class="grid grid-cols-2 gap-3">
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Part Name *</label>
        @if(session('staff_role') === 'admin')
          <input type="text" name="part_name" list="partNameDatalist" placeholder="Select a standard name, or type a new one"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
          <datalist id="partNameDatalist">
            @foreach(\App\Data\PartNames::flat() as $pn)
              <option value="{{ $pn }}"></option>
            @endforeach
          </datalist>
          <p class="text-xs text-gray-400 font-body mt-1">As admin, you can type a new name if it's not listed — this keeps naming consistent for everyone else.</p>
        @else
          <select name="part_name" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
            <option value="">Select a standard part name</option>
            @foreach(\App\Data\PartNames::flat() as $pn)
              <option value="{{ $pn }}">{{ $pn }}</option>
            @endforeach
          </select>
          <p class="text-xs text-gray-400 font-body mt-1">Only admin can add a name not on this list, to keep naming uniform.</p>
        @endif
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Category *</label>
        <select name="part_category" id="categorySelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(['Engine','Transmission','Body','Suspension','Electrical','Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable'] as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat === 'Consumable' ? 'Consumable (Oil, Fluid, Filter)' : $cat }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Condition Grade *</label>
        <select name="condition_grade" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="A">A — Like New / Low Mileage</option>
          <option value="B" selected>B — Good, Minor Wear</option>
          <option value="C">C — Functional, Cosmetic Damage</option>
          <option value="New">New</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1" id="priceLabel">Price (Select Location) *</label>
        <div class="relative">
          <span id="priceSymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-mono text-gray-400">—</span>
          <input type="number" name="price_usd" step="0.01" min="0" placeholder="0.00" id="priceInput"
            class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
        <p class="text-xs text-gray-400 font-body mt-1">Price is fixed in this location's currency — no conversion happens later.</p>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Mileage (miles)</label>
        <input type="number" name="mileage" placeholder="e.g. 85000"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">OEM Part Number</label>
        <input type="text" name="oem_part_number" placeholder="e.g. 19000-0H010"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Colour</label>
        <input type="text" name="colour" placeholder="e.g. Black, Silver"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Side</label>
        <select name="side" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="N/A">N/A</option>
          <option value="Left">Left (Driver)</option>
          <option value="Right">Right (Passenger)</option>
          <option value="Front">Front</option>
          <option value="Rear">Rear</option>
          <option value="Front Left">Front Left</option>
          <option value="Front Right">Front Right</option>
          <option value="Rear Left">Rear Left</option>
          <option value="Rear Right">Rear Right</option>
        </select>
      </div>
    </div>
  </div>

  {{-- ── OEM / Technical Details ───────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">OEM / Technical Details</h2>
    <p class="text-xs text-gray-400 font-body mb-1">These make the part searchable by engine code and transmission code — important for the Nigerian market</p>
    <p id="oemLookupNote" class="text-xs font-body mb-4 text-blue-600"></p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">OEM Engine Code</label>
        <input type="text" name="engine_code_oem" placeholder="e.g. 2ZR-FE, 2AZ-FE, 2GR-FE"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Transmission Code</label>
        <input type="text" name="transmission_code_oem" placeholder="e.g. U341E, U760E, A750E"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Pin Count</label>
        <input type="number" name="pin_count" placeholder="e.g. 13 or 22"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Gear Alias / Market Name</label>
        <input type="text" name="gear_alias" placeholder="e.g. 13-pin gear (Corolla 1.8L)"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Origin Market</label>
        <select name="origin_market" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="N/A">N/A</option>
          <option value="JDM">JDM (Japan)</option>
          <option value="USDM">USDM (USA)</option>
          <option value="EDM">EDM (Europe)</option>
          <option value="Nigerian Used">Nigerian Used</option>
          <option value="Tokunbo">Tokunbo</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin Location</label>
        <input type="text" id="binLocationDisplay" readonly placeholder="Select Location, Store Room & Bin below"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-gray-50 text-gray-500 focus:outline-none">
      </div>
    </div>
  </div>

  {{-- ── Location & Stock ─────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Location & Stock</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
        <select name="location" id="locationSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(['Waxahachie TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Accra Ghana'] as $loc)
            <option value="{{ $loc }}">{{ $loc }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Store Room</label>
        <select id="storeRoomSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Location first</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin</label>
        <select id="binSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Store Room first</option>
        </select>
        <input type="hidden" name="storage_shelf_id" id="storageShelfIdInput">
        <input type="hidden" name="bin_location" id="binLocationInput">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Stock Quantity</label>
        <input type="number" name="stock_qty" id="stockQtyInput" value="1" min="1"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- ── Notes ────────────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Notes & Description</h2>
    <textarea name="description" rows="3" placeholder="Any additional notes about this part..."
      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold"></textarea>
  </div>

  {{-- ── Submit ────────────────────────────────────────────────────── --}}
  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.inventory.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Add to Inventory
    </button>
  </div>

</div>
</form>

<script>
function toggleConsumableFields() {
    const isConsumable = document.getElementById('categorySelect').value === 'Consumable';

    document.getElementById('modelField').style.display     = isConsumable ? 'none' : '';
    document.getElementById('yearFromField').style.display  = isConsumable ? 'none' : '';
    document.getElementById('yearToField').style.display     = isConsumable ? 'none' : '';
    document.getElementById('engineSizeField').style.display = isConsumable ? 'none' : '';
    document.getElementById('unitSizeField').style.display   = isConsumable ? '' : 'none';
    document.getElementById('compatNoteField').style.display = isConsumable ? '' : 'none';

    document.getElementById('brandLabel').textContent = isConsumable ? 'Brand (e.g. Mobil 1, Castrol, Fram)' : 'Make';

    // Model is not required for consumables — give it a sensible default if left blank
    const modelInput = document.getElementById('modelInput');
    if (isConsumable && !modelInput.value) modelInput.value = 'Universal';

    // Year fields aren't required for consumables (server defaults to 1990-2030)
    document.getElementById('yearFromInput').required = !isConsumable;
    document.getElementById('yearToInput').required   = !isConsumable;
}

document.getElementById('categorySelect').addEventListener('change', toggleConsumableFields);
document.addEventListener('DOMContentLoaded', toggleConsumableFields);

// ── Model auto-populate (datalist) — mirrors customer search page ──────────
document.getElementById('brandSelect').addEventListener('change', async function() {
    await loadModels(this.value);
    triggerOemLookup();
});

async function loadModels(make) {
    const datalist = document.getElementById('modelDatalist');
    if (!make || make === 'UNIVERSAL') {
        datalist.innerHTML = '';
        return;
    }
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make.toUpperCase())}`);
        const data = await res.json();
        datalist.innerHTML = (data.models || []).map(m => `<option value="${m}"></option>`).join('');
    } catch (e) {
        datalist.innerHTML = '';
    }
}

// ── OEM / Technical Details auto-populate ──────────────────────────────────
// Triggered on Make/Model/Year change. Checks existing inventory for this
// vehicle first; falls back to OemDatabase suggestion if nothing on file.
// Only fills fields that are currently empty — never overwrites a value
// staff has already typed.
let oemLookupTimer = null;
function triggerOemLookup() {
    clearTimeout(oemLookupTimer);
    oemLookupTimer = setTimeout(runOemLookup, 350);
}

async function runOemLookup() {
    const make       = document.getElementById('brandSelect').value;
    const model      = document.getElementById('modelInput').value;
    const year       = document.getElementById('yearFromInput').value;
    const engineSize = document.getElementById('engineSizeInput').value;

    if (!make || make === 'UNIVERSAL' || !model || !year) return;

    const engineField = document.querySelector('input[name="engine_code_oem"]');
    const transField  = document.querySelector('input[name="transmission_code_oem"]');
    const pinField     = document.querySelector('input[name="pin_count"]');
    const aliasField   = document.querySelector('input[name="gear_alias"]');
    const oemNote       = document.getElementById('oemLookupNote');

    try {
        const params = new URLSearchParams({ make, model, year });
        if (engineSize) params.set('engine_l', engineSize);
        const res  = await fetch(`{{ route('admin.inventory.oem-lookup') }}?${params.toString()}`);
        const data = await res.json();

        if (!data.source) { if (oemNote) oemNote.textContent = ''; return; }

        if (engineField && !engineField.value && data.engine_code) engineField.value = data.engine_code;
        if (transField   && !transField.value   && data.transmission_code) transField.value = data.transmission_code;
        if (pinField      && !pinField.value      && data.pin_count) pinField.value = data.pin_count;
        if (aliasField    && !aliasField.value    && data.gear_alias) aliasField.value = data.gear_alias;

        if (oemNote) {
            if (!engineSize && data.multiple_engines) {
                oemNote.textContent = '⚠ This model has multiple engine options — enter Engine Size (L) above for an accurate match.';
            } else {
                oemNote.textContent = data.source === 'inventory'
                    ? `✓ Auto-filled from ${data.match_count} existing part(s) on file for this vehicle.`
                    : (data.engine_code || data.transmission_code ? '⚠ Suggested codes — not yet confirmed by stock on hand. Please verify.' : '');
            }
        }
    } catch (e) {
        // silent fail — staff can still type OEM fields manually
    }
}

document.getElementById('modelInput').addEventListener('change', triggerOemLookup);
document.getElementById('yearFromInput').addEventListener('change', triggerOemLookup);
document.getElementById('engineSizeInput').addEventListener('change', triggerOemLookup);

// ── Price currency by location — fixed, no conversion ever ────────────────
// Nigeria locations always show ₦, Ghana always shows GH₵, US locations
// always show $. This matches what the server will actually store.
function currencyForLocationName(loc) {
    const l = (loc || '').toLowerCase();
    if (l.includes('nigeria') || l.includes('lagos') || l.includes('ife') || l.includes('ibadan') || l.includes('oshodi')) {
        return { symbol: '₦', code: 'NGN', step: '1' };
    }
    if (l.includes('ghana') || l.includes('accra')) {
        return { symbol: 'GH₵', code: 'GHS', step: '0.01' };
    }
    return { symbol: '$', code: 'USD', step: '0.01' };
}

function updatePriceCurrency() {
    const loc = document.getElementById('locationSelect').value;
    const cur = currencyForLocationName(loc);
    document.getElementById('priceSymbol').textContent = cur.symbol;
    document.getElementById('priceLabel').textContent = `Price (${cur.code}) *`;
    document.getElementById('priceInput').step = cur.step;
}

document.getElementById('locationSelect').addEventListener('change', updatePriceCurrency);
document.addEventListener('DOMContentLoaded', updatePriceCurrency);

// ── Store Room / Bin cascade ───────────────────────────────────────────────
document.getElementById('locationSelect').addEventListener('change', loadStoreRooms);

async function loadStoreRooms() {
    const loc = document.getElementById('locationSelect').value;
    const roomSelect = document.getElementById('storeRoomSelect');
    const binSelect   = document.getElementById('binSelect');

    binSelect.innerHTML = '<option value="">Select Store Room first</option>';
    document.getElementById('storageShelfIdInput').value = '';
    document.getElementById('binLocationInput').value = '';
    document.getElementById('binLocationDisplay').value = '';

    if (!loc) {
        roomSelect.innerHTML = '<option value="">Select Location first</option>';
        return;
    }

    roomSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        if (!data.rooms || data.rooms.length === 0) {
            roomSelect.innerHTML = '<option value="">No store rooms set up for this location yet</option>';
            return;
        }
        roomSelect.innerHTML = '<option value="">Select Store Room</option>' +
            data.rooms.map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    } catch (e) {
        roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
    }
}

document.getElementById('storeRoomSelect').addEventListener('change', loadBinsForRoom);

async function loadBinsForRoom() {
    const roomId = document.getElementById('storeRoomSelect').value;
    const binSelect = document.getElementById('binSelect');

    document.getElementById('storageShelfIdInput').value = '';
    document.getElementById('binLocationInput').value = '';
    document.getElementById('binLocationDisplay').value = '';

    if (!roomId) {
        binSelect.innerHTML = '<option value="">Select Store Room first</option>';
        return;
    }

    binSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/shelves-for-room?room_id=${encodeURIComponent(roomId)}`);
        const data = await res.json();
        if (!data.shelves || data.shelves.length === 0) {
            binSelect.innerHTML = '<option value="">No bins set up for this room yet</option>';
            return;
        }
        binSelect.innerHTML = '<option value="">Select Bin (optional)</option>' +
            data.shelves.map(s => `<option value="${s.id}" data-code="${s.full_bin_code}">${s.full_bin_code}</option>`).join('');
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
}

document.getElementById('binSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const code = selected ? selected.dataset.code : '';
    document.getElementById('storageShelfIdInput').value = this.value;
    document.getElementById('binLocationInput').value = code || '';
    document.getElementById('binLocationDisplay').value = code || '';
});
</script>

@endsection
