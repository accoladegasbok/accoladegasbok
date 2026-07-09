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
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Actual Vehicle Year *</label>
        <input type="number" name="year" id="yearFromInput" placeholder="{{ date('Y') }}" min="1986" max="2027"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <p class="text-xs text-gray-400 font-body mt-1">The real year of the vehicle this part came from — one year, not a range.</p>
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
    {{-- #16 — Compatibility RANGE is separate from the actual donor
         year above. Optional: leave blank and it defaults to the
         actual year (a part only confirmed to fit its own donor year).
         Widen this only when you actually know it fits other years too
         — same pattern as the Edit Part page's Fitment section. --}}
    <div class="mt-3 grid grid-cols-2 gap-3" id="compatRangeField">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatible From Year (optional)</label>
        <input type="number" name="compat_year_from" placeholder="Defaults to actual year" min="1986" max="2027"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatible To Year (optional)</label>
        <input type="number" name="compat_year_to" placeholder="Defaults to actual year" min="1986" max="2027"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <p class="col-span-2 text-xs text-gray-400 font-body">Leave both blank if this part is only confirmed to fit its own actual year above.</p>
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
      <div class="col-span-2 relative">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Part Name *</label>
        {{-- ── Live-filtered search dropdown — replaces the previous
             native <datalist>, which didn't filter reliably as you
             typed (browser-dependent behavior). This matches the same
             working pattern used on the Invoice/Manual line-item
             search (renderSuggestions) and filters instantly on every
             keystroke, same as everywhere else in the system. ── --}}
        <input type="text" name="part_name" id="partNameInput" autocomplete="off" required
          placeholder="Start typing to search standard part names..."
          value="{{ old('part_name') }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <div id="partNameSuggestions" class="absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-full hidden max-h-56 overflow-y-auto"></div>
        @if(session('staff_role') === 'admin')
          <p class="text-xs text-gray-400 font-body mt-1">As admin, you can type a new name if it's not listed — this keeps naming consistent for everyone else.</p>
        @else
          <p class="text-xs text-gray-400 font-body mt-1">Only admin can add a name not on this list, to keep naming uniform. Select one from the dropdown as you type.</p>
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
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Internal / Source Ref <span class="text-gray-400 font-normal">(optional, up to 6 chars)</span></label>
        <input type="text" name="source_ref" maxlength="6" placeholder="e.g. MVE791"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-gold">
        <p class="text-xs text-gray-400 font-body mt-1">Leave blank if not available now — can be added later via Edit.</p>
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
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Drive Type</label>
        <select name="drive_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Not specified</option>
          <option value="2WD">2WD</option>
          <option value="4WD">4WD</option>
          <option value="AWD">AWD</option>
          <option value="RWD">RWD</option>
          <option value="FWD">FWD</option>
          <option value="4x2">4x2</option>
          <option value="4x4">4x4</option>
        </select>
        <p class="text-xs text-gray-400 font-body mt-1">Shown to customers alongside pin count for transmissions/gearboxes.</p>
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
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin Location *</label>
        <input type="text" id="binLocationDisplay" readonly placeholder="Select Location, Store Room & Bin below"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-gray-50 text-gray-500 focus:outline-none">
      </div>
    </div>
  </div>

  {{-- ── Photos — customers will see these, so use clear, well-lit photos ── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Photos <span class="text-gray-400 font-normal text-xs">(optional)</span></h2>
    <p class="text-xs text-gray-400 font-body mb-3">Customers see the first photo on the search page, and all photos once they click in for details. Add as many as you like — more angles helps build trust. If no photo is added, the customer-facing screen shows the AutoZenith default "photo coming soon" image until one is added later.</p>
    <input type="file" name="photos[]" id="photosInput" multiple accept="image/*"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
  </div>

  {{-- ── Video — optional, one per part ── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Video (optional)</h2>
    <p class="text-xs text-gray-400 font-body mb-3">One short video showing the part working/condition — MP4, MOV, AVI or WEBM, max 50MB.</p>
    <input type="file" name="video" accept="video/*"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
  </div>

  {{-- ── Location & Stock ─────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Location & Stock</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
        <select name="location" id="locationSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(\App\Support\Locations::all() as $loc)
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
        <input type="hidden" name="confirm_shared_bin" id="confirmSharedBinInput" value="">
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
    document.getElementById('compatRangeField').style.display = isConsumable ? 'none' : '';
    document.getElementById('engineSizeField').style.display = isConsumable ? 'none' : '';
    document.getElementById('unitSizeField').style.display   = isConsumable ? '' : 'none';
    document.getElementById('compatNoteField').style.display = isConsumable ? '' : 'none';

    document.getElementById('brandLabel').textContent = isConsumable ? 'Brand (e.g. Mobil 1, Castrol, Fram)' : 'Make';

    // Model is not required for consumables — give it a sensible default if left blank
    const modelInput = document.getElementById('modelInput');
    if (isConsumable && !modelInput.value) modelInput.value = 'Universal';

    // Year isn't required for consumables (server defaults to 1990-2030)
    document.getElementById('yearFromInput').required = !isConsumable;
}

document.getElementById('categorySelect').addEventListener('change', toggleConsumableFields);
document.addEventListener('DOMContentLoaded', toggleConsumableFields);

// ── Part Name — live-filtered search dropdown (bug fix) ────────────────────
// Replaces the previous native <datalist>/<select>, which didn't filter
// reliably as you typed. This mirrors the exact working pattern already
// used on the Invoice line-item search (searchParts/renderSuggestions),
// so behavior is now consistent everywhere in the system.
const PART_NAMES_ALL = {!! json_encode(\App\Data\PartNames::flat()) !!};
const IS_ADMIN_STAFF = {{ session('staff_role') === 'admin' ? 'true' : 'false' }};

const partNameInput = document.getElementById('partNameInput');
const partNameBox   = document.getElementById('partNameSuggestions');

partNameInput.addEventListener('input', function() {
    searchPartNames(this.value);
});
partNameInput.addEventListener('focus', function() {
    searchPartNames(this.value);
});

function searchPartNames(query) {
    const q = (query || '').trim().toLowerCase();

    // Filter starting with the typed text first (matches how staff expect
    // "typing the beginning letters" to narrow results), then fall back
    // to a substring match anywhere in the name so partial words still work.
    let matches;
    if (q === '') {
        matches = PART_NAMES_ALL.slice(0, 50);
    } else {
        const startsWith = PART_NAMES_ALL.filter(n => n.toLowerCase().startsWith(q));
        const contains    = PART_NAMES_ALL.filter(n => !n.toLowerCase().startsWith(q) && n.toLowerCase().includes(q));
        matches = [...startsWith, ...contains].slice(0, 50);
    }

    renderPartNameSuggestions(matches, q);
}

function renderPartNameSuggestions(matches, query) {
    if (matches.length === 0) {
        if (IS_ADMIN_STAFF && query) {
            partNameBox.innerHTML = `<div class="px-3 py-3 text-xs text-gray-400">No matching standard name — "${query}" will be added as a new part name (admin only).</div>`;
        } else {
            partNameBox.innerHTML = `<div class="px-3 py-3 text-xs text-gray-400">No matching standard part name found.</div>`;
        }
        partNameBox.classList.remove('hidden');
        return;
    }

    partNameBox.innerHTML = matches.map(name => `
        <div onclick="selectPartName('${name.replace(/'/g, "\\'")}')"
            class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 text-sm text-navy">
            ${name}
        </div>
    `).join('');

    if (IS_ADMIN_STAFF && query) {
        partNameBox.innerHTML += `<div class="px-3 py-2 text-xs text-gray-400 bg-gray-50 italic">Not listed? Keep typing and submit to add "${query}" as a new standard name.</div>`;
    }

    partNameBox.classList.remove('hidden');
}

function selectPartName(name) {
    partNameInput.value = name;
    partNameBox.classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!partNameBox.contains(e.target) && e.target !== partNameInput) {
        partNameBox.classList.add('hidden');
    }
});

// Non-admin staff must pick a name that's actually on the list —
// enforced here client-side (server also enforces this authoritatively
// via assertAllowedPartName(), so this can't be bypassed by disabling JS).
document.querySelector('form').addEventListener('submit', function(e) {
    if (!IS_ADMIN_STAFF) {
        const typed = partNameInput.value.trim();
        const validNames = PART_NAMES_ALL.map(n => n.toLowerCase());
        if (!validNames.includes(typed.toLowerCase())) {
            e.preventDefault();
            alert('Please select a part name from the dropdown list. Only admin accounts can add a name that isn\'t on the standard list.');
            partNameInput.focus();
        }
    }
});

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
            data.shelves.map(s => `<option value="${s.id}" data-code="${s.full_bin_code}" data-occupied="${s.occupied_by ? '1' : ''}">${s.full_bin_code}${s.occupied_by ? ' — occupied: ' + s.occupied_by : ''}</option>`).join('');
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
}

let binSelectPrevValue = '';
document.getElementById('binSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const isOccupied = selected && selected.dataset.occupied === '1';

    if (isOccupied) {
        const ok = confirm(`This bin already has "${selected.textContent.split(' — occupied: ')[1]}" in it.\n\nAre you sure you want to put this item in the same bin? Only do this for genuinely grouped/related items.`);
        if (!ok) {
            this.value = binSelectPrevValue;
            return;
        }
        document.getElementById('confirmSharedBinInput').value = '1';
    } else {
        document.getElementById('confirmSharedBinInput').value = '';
    }

    binSelectPrevValue = this.value;
    const code = selected ? selected.dataset.code : '';
    document.getElementById('storageShelfIdInput').value = this.value;
    document.getElementById('binLocationInput').value = code || '';
    document.getElementById('binLocationDisplay').value = code || '';
});
</script>

@endsection
