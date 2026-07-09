{{-- FILE: resources/views/admin/inventory/manual-add.blade.php --}}
{{-- Harvest-parity manual part entry — VIN decode, single/range year,
     optional photos with coming-soon default, legal trace, major component,
     interchange group assignment, bin location cascade --}}

@extends('admin.layouts.admin')
@section('title', 'Add Part Manually')
@section('page-title', 'Add Part Manually')
@section('page-sub', 'Full harvest-parity form — VIN decode, photos, legal trace, interchange')

@section('content')

<form method="POST" action="{{ route('admin.inventory.store') }}" enctype="multipart/form-data" id="manualAddForm">
@csrf

<div class="max-w-4xl space-y-6">

  {{-- ══════════════════════════════════════════════════════════════
       VIN DECODE — auto-fills vehicle make/model/year/OEM codes
  ══════════════════════════════════════════════════════════════ --}}
  <div class="bg-navy rounded-2xl p-5">
    <div class="text-white font-display font-700 text-sm uppercase tracking-wide mb-1">🔍 VIN Auto-Decode (Optional)</div>
    <p class="text-gray-400 text-xs font-body mb-3">Enter the donor VIN to auto-fill vehicle details, OEM engine/transmission codes and pin count — or fill everything manually below.</p>
    <div class="flex gap-2">
      <div class="flex-1 relative">
        <input type="text" id="vinDecodeInput" maxlength="17"
               placeholder="Enter 17-digit VIN..."
               class="w-full px-4 py-3 rounded-xl text-sm font-mono uppercase tracking-widest border border-white/20 bg-white/10 text-white placeholder:text-gray-500 placeholder:normal-case placeholder:tracking-normal focus:outline-none focus:border-gold">
        <span id="vinCharCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500">0/17</span>
      </div>
      <button type="button" id="vinDecodeBtn"
              class="bg-gold text-navy font-display font-700 text-sm px-5 py-3 rounded-xl hover:bg-yellow-400 transition-colors whitespace-nowrap">
        DECODE VIN
      </button>
    </div>
    <div id="vinStatus" class="mt-2 text-xs font-body hidden"></div>
    <input type="hidden" name="donor_vin" id="donorVinInput">
  </div>

  {{-- ── Part Source / Type ─────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Part Source / Type</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
      @foreach([
        'used_loose'    => ['Used Loose',        'Harvested, no donor VIN',             'bg-amber-500'],
        'new_open'      => ['New Open Box',       'New but opened/unsealed',             'bg-blue-500'],
        'secondary'     => ['Secondary Sourced',  'From another recycler/vendor',        'bg-purple-500'],
        'special_order' => ['Special Order',      'Ordered for specific customer',       'bg-green-500'],
        'generic'       => ['Generic/Universal',  'Oils, filters, fluids, accessories',  'bg-teal-500'],
        'new_oem'       => ['New OEM',            'Brand new original manufacturer',     'bg-gold'],
        'remanufactured'=> ['Remanufactured',     'Rebuilt/reconditioned part',          'bg-orange-500'],
        'core'          => ['Core / Return',      'For rebuild or core exchange only',   'bg-red-500'],
      ] as $val => [$label, $desc, $color])
      <label class="cursor-pointer">
        <input type="radio" name="part_source" value="{{ $val }}" class="sr-only peer" {{ $val === 'used_loose' ? 'checked' : '' }}>
        <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold/10 transition-all">
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

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1" id="brandLabel">Make / Brand</label>
        <select name="brand" id="brandSelect"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Make</option>
          @foreach(\App\Data\VehicleDatabase::makes() as $make)
            <option value="{{ $make }}">{{ $make }}</option>
          @endforeach
          <option value="UNIVERSAL">UNIVERSAL (fits all)</option>
        </select>
      </div>
      <div id="modelField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Model</label>
        <input type="text" name="model" id="modelInput" list="modelDatalist"
               placeholder="Select brand first, or type"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <datalist id="modelDatalist"></datalist>
      </div>

      {{-- Year — single or range --}}
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Year From</label>
        <input type="number" name="year_from" id="yearFromInput"
               placeholder="{{ date('Y') }}" min="1986" max="2027"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div id="yearToField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Year To</label>
        <input type="number" name="year_to" id="yearToInput"
               placeholder="{{ date('Y') }}" min="1986" max="2027"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>

    {{-- Single year toggle --}}
    <label class="flex items-center gap-2 mb-3 cursor-pointer">
      <input type="checkbox" id="singleYearToggle" class="accent-gold w-4 h-4">
      <span class="text-xs text-gray-500 font-body">Single year only (sets Year To = Year From automatically)</span>
    </label>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div id="engineSizeField">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Engine Size (L)</label>
        <input type="number" step="0.1" min="0.5" max="8.0" id="engineSizeInput"
               placeholder="e.g. 2.5"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <p class="text-xs text-gray-400 mt-1">Needed for accurate OEM code lookup</p>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatibility Note</label>
        <input type="text" name="compatibility_note"
               placeholder="e.g. Fits all 4-cyl Toyota models"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- ── Part Details ──────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Part Details</h2>
    <div class="grid grid-cols-2 gap-3">
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Part Name *</label>
        @if(session('staff_role') === 'admin')
          <input type="text" name="part_name" list="partNameDatalist" required
                 placeholder="Select a standard name, or type a new one"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
          <datalist id="partNameDatalist">
            @foreach(\App\Data\PartNames::flat() as $pn)
              <option value="{{ $pn }}"></option>
            @endforeach
          </datalist>
        @else
          <select name="part_name" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
            <option value="">Select a standard part name</option>
            @foreach(\App\Data\PartNames::flat() as $pn)
              <option value="{{ $pn }}">{{ $pn }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Category *</label>
        <select name="part_category" id="categorySelect"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(['Engine','Transmission','Body','Suspension','Electrical','Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable'] as $cat)
            <option value="{{ $cat }}">{{ $cat === 'Consumable' ? 'Consumable (Oil, Fluid, Filter)' : $cat }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Condition Grade *</label>
        <select name="condition_grade"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
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
          <input type="number" name="price_usd" step="0.01" min="0" placeholder="0.00" id="priceInput" required
                 class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
        <p class="text-xs text-gray-400 font-body mt-1">Fixed in this location's currency — no conversion</p>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Wholesale / Trade Price</label>
        <div class="relative">
          <span id="wholesaleSymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-mono text-gray-400">—</span>
          <input type="number" name="price_wholesale" step="0.01" min="0" placeholder="0.00"
                 class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
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
        <select name="side"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
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

    {{-- Powerlink flags --}}
    <div class="mt-4 flex flex-wrap gap-4">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_major_component" value="1" id="majorComponentCheck" class="accent-gold w-4 h-4">
        <span class="text-sm font-body font-600 text-amber-700">⚡ Major Component</span>
        <span class="text-xs text-gray-400">(Engine, Gearbox, Airbag — triggers stricter sale controls)</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="legal_trace_required" value="1" id="legalTraceCheck" class="accent-red-500 w-4 h-4">
        <span class="text-sm font-body font-600 text-red-700">⚠ Legal Trace Required</span>
        <span class="text-xs text-gray-400">(Cat converter, airbag, engine — requires buyer ID at sale)</span>
      </label>
    </div>
  </div>

  {{-- ── OEM / Technical Details (Powerlink + Ladipo algorithm) ─────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">OEM / Technical Details</h2>
    <p class="text-xs text-gray-400 font-body mb-1">
      Powers the compatibility checker and interchange matching (Powerlink + Ladipo Tokunbo market algorithm).
      Auto-filled from VIN decode or existing inventory — verify before saving.
    </p>
    <p id="oemLookupNote" class="text-xs font-body mb-4 text-blue-600 hidden"></p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">OEM Engine Code</label>
        <input type="text" name="engine_code_oem" id="engineCodeOem"
               placeholder="e.g. 2ZR-FE, 2AZ-FE, 2GR-FE"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Transmission Code</label>
        <input type="text" name="transmission_code_oem" id="transCodeOem"
               placeholder="e.g. U341E, U760E, A750E"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Pin Count</label>
        <input type="number" name="pin_count" id="pinCount"
               placeholder="e.g. 13 or 22"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Gear Alias / Market Name</label>
        <input type="text" name="gear_alias" id="gearAlias"
               placeholder="e.g. 13-pin gear (Corolla 1.8L)"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Origin Market</label>
        <select name="origin_market"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="N/A">N/A</option>
          <option value="JDM">JDM (Japan)</option>
          <option value="USDM">USDM (USA)</option>
          <option value="EDM">EDM (Europe)</option>
          <option value="Nigerian Used">Nigerian Used</option>
          <option value="Tokunbo">Tokunbo</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Drive Type</label>
        <select name="drive_type"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Unknown / N/A</option>
          <option value="FWD">FWD</option>
          <option value="RWD">RWD</option>
          <option value="AWD">AWD</option>
          <option value="4WD">4WD</option>
          <option value="4x4">4x4</option>
        </select>
      </div>
    </div>

    {{-- Compat year range (separate from vehicle year) --}}
    <div class="mt-3 grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compat Year From</label>
        <input type="number" name="compat_year_from" id="compatYearFrom"
               placeholder="e.g. 2002" min="1986" max="2030"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
        <p class="text-xs text-gray-400 mt-1">If this part fits a wider year range than the donor vehicle</p>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compat Year To</label>
        <input type="number" name="compat_year_to" id="compatYearTo"
               placeholder="e.g. 2010" min="1986" max="2030"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- ── Photos ──────────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">
      Photos <span class="text-gray-400 font-normal text-xs">(optional — up to 10)</span>
    </h2>
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-3 flex items-start gap-2">
      <span class="text-blue-500 text-lg">📷</span>
      <div>
        <div class="text-xs font-600 text-blue-700">Default: Coming Soon image</div>
        <div class="text-xs text-blue-600">If no photos are uploaded, the part will show the "Photo Coming Soon" placeholder on the public listing until you add real photos from the inventory edit page. You can upload up to 10 photos and 1 video.</div>
      </div>
    </div>
    <input type="file" name="photos[]" id="photosInput" multiple accept="image/*"
           onchange="previewPhotos(this)"
           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    <div id="photoPreview" class="flex gap-2 flex-wrap mt-3"></div>
    <p class="text-xs text-gray-400 font-body mt-2">First photo shown on search page. More angles = more buyer trust. Max 10MB each.</p>
  </div>

  {{-- ── Video ───────────────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Video (optional)</h2>
    <p class="text-xs text-gray-400 font-body mb-3">Short clip showing the part condition — MP4, MOV, AVI or WEBM, max 50MB.</p>
    <input type="file" name="video" accept="video/*"
           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
  </div>

  {{-- ── Location & Stock ─────────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Location & Stock</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
        <select name="location" id="locationSelect"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(['Waxahachie TX','Kennedale TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'] as $loc)
            <option value="{{ $loc }}">{{ $loc }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Store Room</label>
        <select id="storeRoomSelect"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Location first</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin</label>
        <select id="binSelect"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Store Room first</option>
        </select>
        <input type="hidden" name="storage_shelf_id" id="storageShelfIdInput">
        <input type="hidden" name="bin_location" id="binLocationInput">
        <input type="hidden" name="confirm_shared_bin" id="confirmSharedBinInput" value="">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin Display</label>
        <input type="text" id="binLocationDisplay" readonly
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body bg-gray-50 text-gray-500 focus:outline-none">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Stock Quantity</label>
        <input type="number" name="stock_qty" value="1" min="1"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
  </div>

  {{-- ── Conditions & Notes ──────────────────────────────────────── --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Conditions & Notes</h2>
    <div class="space-y-3">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">
          Conditions / Options <span class="text-gray-300 font-normal">(max 36 chars — printed on label)</span>
        </label>
        <input type="text" name="conditions_and_options" maxlength="36"
               placeholder="e.g. Minor scratch on housing, tested good"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Full Description / Notes</label>
        <textarea name="description" rows="3"
                  placeholder="Any additional notes about this part, sourcing details, etc."
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold resize-none"></textarea>
      </div>
    </div>
  </div>

  {{-- ── Submit ────────────────────────────────────────────────────── --}}
  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.inventory.index') }}"
       class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
            class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      Add to Inventory
    </button>
  </div>

</div>
</form>

<script>
// ═══════════════════════════════════════════════════════════════
// VIN DECODE — auto-fills vehicle + OEM fields
// ═══════════════════════════════════════════════════════════════
const vinInput = document.getElementById('vinDecodeInput');
const vinCount = document.getElementById('vinCharCount');
const vinBtn   = document.getElementById('vinDecodeBtn');
const vinStatus = document.getElementById('vinStatus');

vinInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
    vinCount.textContent = `${this.value.length}/17`;
    vinCount.className = `absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono ${this.value.length === 17 ? 'text-green-400 font-500' : 'text-gray-500'}`;
});

vinInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); decodeVin(); }});
vinBtn.addEventListener('click', decodeVin);

async function decodeVin() {
    const vin = vinInput.value.trim();
    if (vin.length !== 17) {
        showStatus('VIN must be exactly 17 characters.', 'error');
        return;
    }

    vinBtn.disabled = true;
    vinBtn.textContent = 'Decoding...';

    try {
        const res = await fetch('{{ route('admin.harvest.vin-decode') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ vin })
        });
        const data = await res.json();

        if (data.error || !data.vehicle) {
            showStatus(data.error || 'Could not decode this VIN.', 'error');
            return;
        }

        const v = data.vehicle;

        // Fill vehicle fields
        if (v.make) {
            document.getElementById('brandSelect').value = v.make.toUpperCase();
            await loadModels(v.make.toUpperCase());
        }
        if (v.model) document.getElementById('modelInput').value = v.model.toUpperCase();
        if (v.year)  {
            document.getElementById('yearFromInput').value = v.year;
            document.getElementById('yearToInput').value = v.year;
        }

        // Fill OEM fields if empty
        const engineField = document.getElementById('engineCodeOem');
        const transField  = document.getElementById('transCodeOem');
        const pinField    = document.getElementById('pinCount');
        const aliasField  = document.getElementById('gearAlias');

        if (v.oem_engine_code       && !engineField.value) engineField.value = v.oem_engine_code;
        if (v.oem_transmission_code && !transField.value)  transField.value  = v.oem_transmission_code;
        if (v.pin_count             && !pinField.value)    pinField.value    = v.pin_count;
        if (v.gear_alias            && !aliasField.value)  aliasField.value  = v.gear_alias;

        // Store donor VIN
        document.getElementById('donorVinInput').value = vin;

        // Drive type
        if (v.drive_type) {
            const driveSelect = document.querySelector('select[name="drive_type"]');
            if (driveSelect) driveSelect.value = v.drive_type.replace('4-Wheel Drive', '4WD').replace('Front-Wheel Drive','FWD').replace('Rear-Wheel Drive','RWD').replace('All-Wheel Drive','AWD').split(' ')[0] || '';
        }

        showStatus(`✓ Decoded: ${v.year} ${v.make} ${v.model}${v.oem_engine_code ? ' · Engine: '+v.oem_engine_code : ''}${v.oem_transmission_code ? ' · Trans: '+v.oem_transmission_code : ''}`, 'success');

    } catch (e) {
        showStatus('Network error decoding VIN. Fill manually below.', 'error');
    } finally {
        vinBtn.disabled = false;
        vinBtn.textContent = 'DECODE VIN';
    }
}

function showStatus(msg, type) {
    vinStatus.textContent = msg;
    vinStatus.className = `mt-2 text-xs font-body ${type === 'success' ? 'text-green-400' : 'text-red-400'}`;
    vinStatus.classList.remove('hidden');
}

// ═══════════════════════════════════════════════════════════════
// SINGLE YEAR TOGGLE
// ═══════════════════════════════════════════════════════════════
document.getElementById('singleYearToggle').addEventListener('change', function() {
    const yearToField = document.getElementById('yearToField');
    const yearFrom = document.getElementById('yearFromInput');
    const yearTo   = document.getElementById('yearToInput');

    if (this.checked) {
        yearToField.style.opacity = '0.5';
        yearToField.style.pointerEvents = 'none';
        // Mirror year_from → year_to
        yearTo.value = yearFrom.value;
        yearFrom.addEventListener('input', syncYear);
    } else {
        yearToField.style.opacity = '';
        yearToField.style.pointerEvents = '';
        yearFrom.removeEventListener('input', syncYear);
    }
});

function syncYear() {
    document.getElementById('yearToInput').value = document.getElementById('yearFromInput').value;
}

// ═══════════════════════════════════════════════════════════════
// MODEL CASCADE
// ═══════════════════════════════════════════════════════════════
document.getElementById('brandSelect').addEventListener('change', async function() {
    await loadModels(this.value);
    triggerOemLookup();
});

async function loadModels(make) {
    const datalist = document.getElementById('modelDatalist');
    if (!make || make === 'UNIVERSAL') { datalist.innerHTML = ''; return; }
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make.toUpperCase())}`);
        const data = await res.json();
        datalist.innerHTML = (data.models || []).map(m => `<option value="${m}"></option>`).join('');
    } catch (e) { datalist.innerHTML = ''; }
}

// ═══════════════════════════════════════════════════════════════
// OEM LOOKUP (Powerlink + Ladipo algorithm)
// ═══════════════════════════════════════════════════════════════
let oemLookupTimer = null;
function triggerOemLookup() {
    clearTimeout(oemLookupTimer);
    oemLookupTimer = setTimeout(runOemLookup, 350);
}

async function runOemLookup() {
    const make  = document.getElementById('brandSelect').value;
    const model = document.getElementById('modelInput').value;
    const year  = document.getElementById('yearFromInput').value;
    const eng   = document.getElementById('engineSizeInput').value;
    if (!make || make === 'UNIVERSAL' || !model || !year) return;

    const engineField = document.getElementById('engineCodeOem');
    const transField  = document.getElementById('transCodeOem');
    const pinField    = document.getElementById('pinCount');
    const aliasField  = document.getElementById('gearAlias');
    const note        = document.getElementById('oemLookupNote');

    try {
        const params = new URLSearchParams({ make, model, year });
        if (eng) params.set('engine_l', eng);
        const res  = await fetch(`{{ route('admin.inventory.oem-lookup') }}?${params.toString()}`);
        const data = await res.json();
        if (!data.source) { note.classList.add('hidden'); return; }
        if (engineField && !engineField.value && data.engine_code)       engineField.value = data.engine_code;
        if (transField  && !transField.value  && data.transmission_code) transField.value  = data.transmission_code;
        if (pinField    && !pinField.value    && data.pin_count)         pinField.value    = data.pin_count;
        if (aliasField  && !aliasField.value  && data.gear_alias)        aliasField.value  = data.gear_alias;
        if (note) {
            if (!eng && data.multiple_engines) {
                note.textContent = '⚠ Multiple engine options — enter Engine Size (L) for accurate match.';
            } else {
                note.textContent = data.source === 'inventory'
                    ? `✓ OEM codes auto-filled from ${data.match_count} existing part(s) on file.`
                    : (data.engine_code ? '⚠ Suggested codes — not yet confirmed by stock. Verify before saving.' : '');
            }
            note.classList.remove('hidden');
        }
    } catch (e) {}
}

document.getElementById('modelInput').addEventListener('change', triggerOemLookup);
document.getElementById('yearFromInput').addEventListener('change', function() {
    triggerOemLookup();
    // Auto-detect major component and legal trace by part name
    autoDetectFlags();
});
document.getElementById('engineSizeInput').addEventListener('change', triggerOemLookup);

// ═══════════════════════════════════════════════════════════════
// AUTO-DETECT LEGAL TRACE + MAJOR COMPONENT FLAGS
// ═══════════════════════════════════════════════════════════════
const MAJOR_KEYWORDS   = ['engine','gearbox','transmission','airbag','frame','chassis'];
const LEGAL_KEYWORDS   = ['catalytic','cat converter','airbag','engine','transmission'];

function autoDetectFlags() {
    const partName = (document.querySelector('[name="part_name"]')?.value || '').toLowerCase();
    if (!partName) return;

    const isMajor = MAJOR_KEYWORDS.some(k => partName.includes(k));
    const isLegal = LEGAL_KEYWORDS.some(k => partName.includes(k));

    if (isMajor && !document.getElementById('majorComponentCheck').checked) {
        document.getElementById('majorComponentCheck').checked = true;
    }
    if (isLegal && !document.getElementById('legalTraceCheck').checked) {
        document.getElementById('legalTraceCheck').checked = true;
    }
}

document.querySelector('[name="part_name"]')?.addEventListener('change', autoDetectFlags);
document.querySelector('[name="part_name"]')?.addEventListener('input', autoDetectFlags);

// ═══════════════════════════════════════════════════════════════
// PRICE CURRENCY BY LOCATION
// ═══════════════════════════════════════════════════════════════
function currencyForLocation(loc) {
    const l = (loc || '').toLowerCase();
    if (l.includes('nigeria') || l.includes('ife') || l.includes('ibadan') || l.includes('lagos') || l.includes('abuja') || l.includes('akure')) {
        return { symbol: '₦', code: 'NGN', step: '1' };
    }
    if (l.includes('ghana') || l.includes('accra')) {
        return { symbol: 'GH₵', code: 'GHS', step: '0.01' };
    }
    return { symbol: '$', code: 'USD', step: '0.01' };
}

function updatePriceCurrency() {
    const loc = document.getElementById('locationSelect').value;
    const cur = currencyForLocation(loc);
    document.getElementById('priceSymbol').textContent     = cur.symbol;
    document.getElementById('wholesaleSymbol').textContent = cur.symbol;
    document.getElementById('priceLabel').textContent      = `Price (${cur.code}) *`;
    document.getElementById('priceInput').step             = cur.step;
}

document.getElementById('locationSelect').addEventListener('change', function() {
    updatePriceCurrency();
    loadStoreRooms();
});
document.addEventListener('DOMContentLoaded', updatePriceCurrency);

// ═══════════════════════════════════════════════════════════════
// STORE ROOM / BIN CASCADE
// ═══════════════════════════════════════════════════════════════
async function loadStoreRooms() {
    const loc        = document.getElementById('locationSelect').value;
    const roomSelect = document.getElementById('storeRoomSelect');
    const binSelect  = document.getElementById('binSelect');

    binSelect.innerHTML = '<option value="">Select Store Room first</option>';
    document.getElementById('storageShelfIdInput').value = '';
    document.getElementById('binLocationInput').value = '';
    document.getElementById('binLocationDisplay').value = '';

    if (!loc) { roomSelect.innerHTML = '<option value="">Select Location first</option>'; return; }

    roomSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        if (!data.rooms || data.rooms.length === 0) {
            roomSelect.innerHTML = '<option value="">No rooms set up for this location</option>'; return;
        }
        roomSelect.innerHTML = '<option value="">Select Store Room</option>' +
            data.rooms.map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    } catch (e) {
        roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
    }
}

document.getElementById('storeRoomSelect').addEventListener('change', async function() {
    const roomId    = this.value;
    const binSelect = document.getElementById('binSelect');
    document.getElementById('storageShelfIdInput').value = '';
    document.getElementById('binLocationInput').value = '';
    document.getElementById('binLocationDisplay').value = '';

    if (!roomId) { binSelect.innerHTML = '<option value="">Select Store Room first</option>'; return; }
    binSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/shelves-for-room?room_id=${encodeURIComponent(roomId)}`);
        const data = await res.json();
        if (!data.shelves || data.shelves.length === 0) {
            binSelect.innerHTML = '<option value="">No bins set up yet</option>'; return;
        }
        binSelect.innerHTML = '<option value="">Select Bin (optional)</option>' +
            data.shelves.map(s => `<option value="${s.id}" data-code="${s.full_bin_code}" data-occupied="${s.occupied_by ? '1' : ''}">${s.full_bin_code}${s.occupied_by ? ' — ' + s.occupied_by : ''}</option>`).join('');
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
});

let binPrev = '';
document.getElementById('binSelect').addEventListener('change', function() {
    const sel = this.options[this.selectedIndex];
    if (sel && sel.dataset.occupied === '1') {
        const ok = confirm(`This bin already has "${sel.textContent.split(' — ')[1]}" in it.\nShare this bin?`);
        if (!ok) { this.value = binPrev; return; }
        document.getElementById('confirmSharedBinInput').value = '1';
    } else {
        document.getElementById('confirmSharedBinInput').value = '';
    }
    binPrev = this.value;
    const code = sel ? sel.dataset.code : '';
    document.getElementById('storageShelfIdInput').value  = this.value;
    document.getElementById('binLocationInput').value     = code || '';
    document.getElementById('binLocationDisplay').value   = code || '';
});

// ═══════════════════════════════════════════════════════════════
// PHOTO PREVIEW
// ═══════════════════════════════════════════════════════════════
function previewPhotos(input) {
    const preview = document.getElementById('photoPreview');
    preview.innerHTML = '';
    if (input.files.length > 10) {
        alert('Maximum 10 photos allowed. Only the first 10 will be used.');
    }
    const files = Array.from(input.files).slice(0, 10);
    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'relative';
            div.innerHTML = `<img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border-2 ${i === 0 ? 'border-gold' : 'border-gray-200'}">
                ${i === 0 ? '<div class="absolute -top-1 -right-1 bg-gold text-navy text-[9px] font-700 px-1 rounded">MAIN</div>' : ''}`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// ═══════════════════════════════════════════════════════════════
// CONSUMABLE TOGGLE (hide vehicle fields)
// ═══════════════════════════════════════════════════════════════
function toggleConsumableFields() {
    const isConsumable = document.getElementById('categorySelect').value === 'Consumable';
    document.getElementById('modelField').style.display      = isConsumable ? 'none' : '';
    document.getElementById('yearFromInput').closest('div').style.display = isConsumable ? 'none' : '';
    document.getElementById('yearToField').style.display     = isConsumable ? 'none' : '';
    document.getElementById('engineSizeField').style.display = isConsumable ? 'none' : '';
    document.getElementById('brandLabel').textContent        = isConsumable ? 'Brand (e.g. Mobil 1, Castrol)' : 'Make / Brand';
    if (isConsumable && !document.getElementById('modelInput').value) {
        document.getElementById('modelInput').value = 'Universal';
    }
}
document.getElementById('categorySelect').addEventListener('change', toggleConsumableFields);
document.addEventListener('DOMContentLoaded', toggleConsumableFields);
</script>

@endsection
