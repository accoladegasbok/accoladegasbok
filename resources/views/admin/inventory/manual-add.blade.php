{{-- FILE: resources/views/admin/inventory/manual-add.blade.php --}}
{{-- Matches InventoryController::store() exactly:
     Required: brand, part_name, part_category, price_usd, condition_grade,
               location, storage_shelf_id, year (single), model
     Optional: photos[], video, compat_year_from, compat_year_to,
               engine_code_oem, transmission_code_oem, pin_count, etc. --}}

@extends('admin.layouts.admin')
@section('title', 'Add Part Manually')
@section('page-title', 'Add Part Manually')
@section('page-sub', 'Full harvest-parity form — VIN decode, single year, auto OEM codes, photos, bin')

@section('content')

<form method="POST" action="{{ route('admin.inventory.store') }}"
      enctype="multipart/form-data" id="manualAddForm">
@csrf

{{-- Show validation errors prominently --}}
@if($errors->any())
<div class="bg-red-50 border border-red-300 rounded-2xl px-5 py-4 mb-5">
    <div class="font-700 text-red-700 text-sm mb-2">⚠ Please fix the following:</div>
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)
        <li class="text-sm text-red-600">{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="max-w-4xl space-y-6">

{{-- ── VIN DECODE ─────────────────────────────────────────────── --}}
<div class="bg-navy rounded-2xl p-5">
    <div class="text-white font-display font-700 text-sm uppercase tracking-wide mb-1">🔍 VIN Auto-Decode (Optional)</div>
    <p class="text-gray-400 text-xs font-body mb-3">Enter a 17-digit VIN to auto-fill Make, Model, Year and OEM codes. Or fill everything manually below.</p>
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
    <input type="hidden" name="donor_vin" id="donorVinHidden">
</div>

{{-- ── VEHICLE FITMENT ────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Vehicle Fitment *</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Make / Brand *</label>
            <select name="brand" id="brandSelect" required
                    onchange="onBrandChange(this.value)"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                <option value="">Select Make</option>
                @foreach(\App\Data\VehicleDatabase::makes() as $make)
                <option value="{{ $make }}" {{ old('brand')===$make?'selected':'' }}>{{ $make }}</option>
                @endforeach
                <option value="UNIVERSAL" {{ old('brand')==='UNIVERSAL'?'selected':'' }}>UNIVERSAL (fits all)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
            <input type="text" name="model" id="modelInput" list="modelDatalist"
                   value="{{ old('model') }}"
                   placeholder="Select brand first, or type"
                   required
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
            <datalist id="modelDatalist"></datalist>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">
                Year * <span class="text-gray-400 font-normal text-[10px]">(single year of donor vehicle)</span>
            </label>
            <input type="number" name="year" id="yearInput"
                   value="{{ old('year') }}"
                   placeholder="{{ date('Y') }}" min="1986" max="{{ date('Y') + 1 }}"
                   required
                   onchange="triggerOemLookup()"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
            <p class="text-[10px] text-gray-400 mt-1">Year the part was harvested from</p>
        </div>
    </div>

    {{-- Compat year range (optional — wider than donor year) --}}
    <div class="mt-3 border-t border-gray-100 pt-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-2">
            Compatibility Year Range <span class="text-gray-400 font-normal">(optional — if this part fits a wider range than the donor year)</span>
        </label>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <input type="number" name="compat_year_from" id="compatYearFrom"
                       value="{{ old('compat_year_from') }}"
                       placeholder="From — e.g. 2002" min="1986" max="2030"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
            </div>
            <div>
                <input type="number" name="compat_year_to" id="compatYearTo"
                       value="{{ old('compat_year_to') }}"
                       placeholder="To — e.g. 2011" min="1986" max="2030"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
            </div>
        </div>
        <p id="oemNote" class="text-xs text-blue-600 mt-1 hidden"></p>
    </div>
</div>

{{-- ── PART DETAILS ───────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Part Details *</h2>
    <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Part Name *</label>
            <input type="text" name="part_name" list="partNameDatalist"
                   value="{{ old('part_name') }}"
                   placeholder="Start typing or select a standard part name"
                   required
                   onchange="autoDetectFlags()"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
            <datalist id="partNameDatalist">
                @foreach(\App\Data\PartNames::flat() as $pn)
                <option value="{{ $pn }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Category *</label>
            <select name="part_category" id="categorySelect" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                @foreach(['Engine','Transmission','Body','Suspension','Electrical','Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable'] as $cat)
                <option value="{{ $cat }}" {{ old('part_category')===$cat?'selected':'' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Condition Grade *</label>
            <select name="condition_grade" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                <option value="A" {{ old('condition_grade')==='A'?'selected':'' }}>A — Like New / Low Mileage</option>
                <option value="B" {{ old('condition_grade')==='B'?'selected':'' }} selected>B — Good, Minor Wear</option>
                <option value="C" {{ old('condition_grade')==='C'?'selected':'' }}>C — Functional, Cosmetic Damage</option>
                <option value="New" {{ old('condition_grade')==='New'?'selected':'' }}>New</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5" id="priceLabel">Price *</label>
            <div class="relative">
                <span id="priceSymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-mono text-gray-400">$</span>
                <input type="number" name="price_usd" step="0.01" min="0"
                       value="{{ old('price_usd') }}"
                       placeholder="0.00" id="priceInput" required
                       class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
            </div>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Wholesale / Trade Price</label>
            <div class="relative">
                <span id="wholesaleSymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-mono text-gray-400">$</span>
                <input type="number" name="price_wholesale" step="0.01" min="0"
                       value="{{ old('price_wholesale') }}"
                       placeholder="0.00"
                       class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
            </div>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Side</label>
            <select name="side"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                @foreach(['N/A','Left','Right','Front','Rear','Front Left','Front Right','Rear Left','Rear Right'] as $s)
                <option value="{{ $s }}" {{ old('side')===$s?'selected':'' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Mileage (miles)</label>
            <input type="number" name="mileage" value="{{ old('mileage') }}" placeholder="e.g. 85000"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Colour</label>
            <input type="text" name="colour" value="{{ old('colour') }}" placeholder="e.g. Black, Silver"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">OEM Part Number</label>
            <input type="text" name="oem_part_number" value="{{ old('oem_part_number') }}" placeholder="e.g. 19000-0H010"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
    </div>

    {{-- Flags --}}
    <div class="mt-4 flex flex-wrap gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_major_component" value="1" id="majorCheck" class="accent-gold w-4 h-4"
                   {{ old('is_major_component') ? 'checked' : '' }}>
            <span class="text-sm font-600 text-amber-700">⚡ Major Component</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="legal_trace_required" value="1" id="legalCheck" class="w-4 h-4"
                   {{ old('legal_trace_required') ? 'checked' : '' }}>
            <span class="text-sm font-600 text-red-700">⚠ Legal Trace Required</span>
        </label>
    </div>
</div>

{{-- ── OEM TECHNICAL DETAILS ──────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">OEM / Technical Details</h2>
    <p class="text-xs text-gray-400 mb-3">Auto-filled from VIN decode or Make/Model/Year — powers compatibility checker and interchange matching.</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Engine Code</label>
            <input type="text" name="engine_code_oem" id="engineCodeOem"
                   value="{{ old('engine_code_oem') }}"
                   placeholder="e.g. 2ZR-FE, K24A"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Transmission Code</label>
            <input type="text" name="transmission_code_oem" id="transCodeOem"
                   value="{{ old('transmission_code_oem') }}"
                   placeholder="e.g. U241E, U760E"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Pin Count</label>
            <input type="number" name="pin_count" id="pinCount"
                   value="{{ old('pin_count') }}"
                   placeholder="e.g. 13 or 22"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Gear Alias</label>
            <input type="text" name="gear_alias" id="gearAlias"
                   value="{{ old('gear_alias') }}"
                   placeholder="e.g. 13-pin gear (Camry 2.4L)"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Drive Type</label>
            <select name="drive_type"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                <option value="">Unknown / N/A</option>
                @foreach(['FWD','RWD','AWD','4WD','4x4'] as $dt)
                <option value="{{ $dt }}" {{ old('drive_type')===$dt?'selected':'' }}>{{ $dt }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Origin Market</label>
            <select name="origin_market"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                @foreach(['N/A','JDM','USDM','EDM','Nigerian Used','Tokunbo'] as $o)
                <option value="{{ $o }}" {{ old('origin_market')===$o?'selected':'' }}>{{ $o }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- ── PHOTOS ─────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">
        Photos <span class="text-gray-400 font-normal text-xs">(optional — up to 10)</span>
    </h2>
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-3 text-xs text-blue-700">
        📷 If no photos are uploaded, the part shows "Photo Coming Soon" on the public listing until you add photos from the inventory edit page.
    </div>
    <input type="file" name="photos[]" multiple accept="image/*"
           onchange="previewPhotos(this)"
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
    <div id="photoPreview" class="flex gap-2 flex-wrap mt-3"></div>
</div>

{{-- ── VIDEO ──────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Video <span class="text-gray-400 font-normal text-xs">(optional)</span></h2>
    <input type="file" name="video" accept="video/*"
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
</div>

{{-- ── LOCATION & BIN ─────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Location & Storage *</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Warehouse Location *</label>
            <select name="location" id="locationSelect" required
                    onchange="updatePriceCurrency(); loadStoreRooms();"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                @foreach(['Waxahachie TX','Kennedale TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'] as $loc)
                <option value="{{ $loc }}" {{ old('location')===$loc?'selected':'' }}>{{ $loc }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Store Room *</label>
            <select id="storeRoomSelect" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                <option value="">Select Location first</option>
            </select>
            <input type="hidden" name="storage_room_id" id="storageRoomId">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">
                Bin <span class="text-gray-400 font-normal">(optional)</span>
            </label>
            <select id="binSelect"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-gold">
                <option value="">No specific bin</option>
            </select>
            <input type="hidden" name="storage_shelf_id" id="storageShelfId" value="">
            <input type="hidden" name="bin_location"     id="binLocationHidden" value="">
            <input type="hidden" name="confirm_shared_bin" id="confirmSharedBin" value="">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Selected Bin</label>
            <input type="text" id="binDisplay" readonly placeholder="None selected"
                   class="w-full border border-gray-100 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500">
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">
        Room is required. Bin is optional — parts can be stored at room level and assigned to a bin later from the edit page.
        If a bin is already occupied, you'll be prompted to confirm sharing.
    </p>
    <div class="mt-3">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Stock Quantity</label>
        <input type="number" name="stock_qty" value="{{ old('stock_qty', 1) }}" min="1"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold w-32">
    </div>
</div>

{{-- ── NOTES ──────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Notes & References</h2>
    <div class="space-y-3">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">
                Source / External Reference <span class="text-gray-400 font-normal">(optional — max 6 alphanumeric)</span>
            </label>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 text-xs font-mono text-gray-400 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 whitespace-nowrap">
                    <span id="partCodePreview">ENG-XXXXX</span> /
                </div>
                <input type="text" name="source_ref" maxlength="6"
                       value="{{ old('source_ref') }}"
                       placeholder="e.g. FEM123"
                       oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,''); document.getElementById('sourceRefPreview').textContent=this.value||'------'"
                       class="w-40 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-gold tracking-widest">
                <span id="sourceRefPreview" class="text-xs font-mono text-gray-400">------</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                Shown as <span class="font-mono text-navy">ENG-00018 / FEM123</span> on labels and listings.
                For harvested parts: use last 6 of VIN. For purchased parts: supplier stock number.
            </p>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">
                Conditions / Options <span class="text-gray-400 font-normal">(max 36 chars — printed on label)</span>
            </label>
            <input type="text" name="conditions_and_options" maxlength="36"
                   value="{{ old('conditions_and_options') }}"
                   placeholder="e.g. Minor scratch on housing, tested good"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Full Description</label>
            <textarea name="description" rows="2"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold resize-none">{{ old('description') }}</textarea>
        </div>
    </div>
</div>

{{-- ── SUBMIT ─────────────────────────────────────────────────── --}}
<div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.inventory.index') }}"
       class="border border-gray-200 text-gray-600 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
        Cancel
    </a>
    <button type="submit"
            class="bg-gold text-navy font-display font-700 text-sm px-8 py-3 rounded-xl hover:bg-yellow-500 transition-colors shadow-lg">
        ✓ Add to Inventory
    </button>
</div>

</div>
</form>

<script>
// ── VIN DECODE ─────────────────────────────────────────────────
const vinInput = document.getElementById('vinDecodeInput');
const vinCount = document.getElementById('vinCharCount');
const vinBtn   = document.getElementById('vinDecodeBtn');

vinInput?.addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
    vinCount.textContent = `${this.value.length}/17`;
    vinCount.className   = `absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono ${this.value.length===17?'text-green-400':'text-gray-500'}`;
});
vinInput?.addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();decodeVin();} });
vinBtn?.addEventListener('click', decodeVin);

async function decodeVin() {
    const vin = vinInput.value.trim();
    if(vin.length!==17){showVinStatus('VIN must be exactly 17 characters.','error');return;}
    vinBtn.disabled=true; vinBtn.textContent='Decoding...';
    try {
        const res  = await fetch('{{ route('admin.harvest.vin-decode') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'{{ csrf_token() }}'},
            body:JSON.stringify({vin})
        });
        const data = await res.json();
        if(data.error||!data.vehicle){showVinStatus(data.error||'Could not decode VIN.','error');return;}
        const v = data.vehicle;
        if(v.make)  { document.getElementById('brandSelect').value = v.make.toUpperCase(); await loadModels(v.make.toUpperCase()); }
        if(v.model) document.getElementById('modelInput').value = v.model.toUpperCase();
        if(v.year)  document.getElementById('yearInput').value = v.year;
        document.getElementById('donorVinHidden').value = vin;
        if(v.oem_engine_code && !document.getElementById('engineCodeOem').value)       document.getElementById('engineCodeOem').value = v.oem_engine_code;
        if(v.oem_transmission_code && !document.getElementById('transCodeOem').value)  document.getElementById('transCodeOem').value = v.oem_transmission_code;
        if(v.pin_count && !document.getElementById('pinCount').value)                  document.getElementById('pinCount').value = v.pin_count;
        if(v.gear_alias && !document.getElementById('gearAlias').value)               document.getElementById('gearAlias').value = v.gear_alias;
        if(v.drive_type) {
            const dtMap = {'Front-Wheel Drive':'FWD','Rear-Wheel Drive':'RWD','All-Wheel Drive':'AWD','4-Wheel Drive':'4WD'};
            const mapped = dtMap[v.drive_type] || v.drive_type.split(' ')[0];
            document.querySelector('select[name="drive_type"]').value = mapped;
        }
        showVinStatus(`✓ Decoded: ${v.year} ${v.make} ${v.model}`,'success');
        triggerOemLookup();
    } catch(e){showVinStatus('Network error decoding VIN.','error');}
    finally{vinBtn.disabled=false;vinBtn.textContent='DECODE VIN';}
}

function showVinStatus(msg,type){
    const el=document.getElementById('vinStatus');
    el.textContent=msg; el.className=`mt-2 text-xs font-body ${type==='success'?'text-green-400':'text-red-400'}`;
    el.classList.remove('hidden');
}

// ── MODEL CASCADE ───────────────────────────────────────────────
async function onBrandChange(make) { await loadModels(make); triggerOemLookup(); }
async function loadModels(make) {
    const dl=document.getElementById('modelDatalist');
    if(!make||make==='UNIVERSAL'){dl.innerHTML='';return;}
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make)}`);
        const data = await res.json();
        dl.innerHTML=(data.models||[]).map(m=>`<option value="${m}">`).join('');
    }catch(e){dl.innerHTML='';}
}

// ── OEM LOOKUP ──────────────────────────────────────────────────
let oemTimer=null;
function triggerOemLookup(){clearTimeout(oemTimer);oemTimer=setTimeout(runOemLookup,400);}
document.getElementById('modelInput')?.addEventListener('change',triggerOemLookup);
document.getElementById('yearInput')?.addEventListener('change',function(){
    triggerOemLookup();
    // Auto-copy year to compat fields if empty
    const yr = this.value;
    if(yr && !document.getElementById('compatYearFrom').value) document.getElementById('compatYearFrom').value = yr;
    if(yr && !document.getElementById('compatYearTo').value)   document.getElementById('compatYearTo').value   = yr;
});

async function runOemLookup(){
    const make  = document.getElementById('brandSelect').value;
    const model = document.getElementById('modelInput').value;
    const year  = document.getElementById('yearInput').value;
    if(!make||make==='UNIVERSAL'||!model||!year) return;
    try {
        const res  = await fetch(`{{ route('admin.inventory.oem-lookup') }}?make=${encodeURIComponent(make)}&model=${encodeURIComponent(model)}&year=${year}`);
        const data = await res.json();
        if(!data.source) return;
        if(!document.getElementById('engineCodeOem').value && data.engine_code)       document.getElementById('engineCodeOem').value = data.engine_code;
        if(!document.getElementById('transCodeOem').value  && data.transmission_code) document.getElementById('transCodeOem').value  = data.transmission_code;
        if(!document.getElementById('pinCount').value      && data.pin_count)         document.getElementById('pinCount').value      = data.pin_count;
        if(!document.getElementById('gearAlias').value     && data.gear_alias)        document.getElementById('gearAlias').value     = data.gear_alias;
        // Auto-populate compat year range from OemDatabase
        if(data.compat_year_from && !document.getElementById('compatYearFrom').value) document.getElementById('compatYearFrom').value = data.compat_year_from;
        if(data.compat_year_to   && !document.getElementById('compatYearTo').value)   document.getElementById('compatYearTo').value   = data.compat_year_to;
        const note=document.getElementById('oemNote');
        if(note){
            note.textContent = data.source==='inventory'
                ? `✓ OEM codes filled from ${data.match_count} existing part(s) in inventory.`
                : `⚠ OEM codes suggested from database — verify before saving.`;
            note.classList.remove('hidden');
        }
    }catch(e){}
}

// ── AUTO-DETECT FLAGS ───────────────────────────────────────────
function autoDetectFlags(){
    const n=(document.querySelector('[name="part_name"]')?.value||'').toLowerCase();
    if(['engine','gearbox','transmission','airbag','catalytic','cat converter'].some(k=>n.includes(k))){
        document.getElementById('majorCheck').checked=true;
        document.getElementById('legalCheck').checked=true;
    } else if(['frame','chassis','abs module'].some(k=>n.includes(k))){
        document.getElementById('majorCheck').checked=true;
    }
}

// ── PRICE CURRENCY ──────────────────────────────────────────────
function currencyForLoc(loc){
    const l=(loc||'').toLowerCase();
    if(l.includes('nigeria')||l.includes('ife')||l.includes('ibadan')||l.includes('lagos')||l.includes('abuja')||l.includes('akure')) return {sym:'₦',code:'NGN'};
    if(l.includes('ghana')||l.includes('accra')) return {sym:'GH₵',code:'GHS'};
    return {sym:'$',code:'USD'};
}
function updatePriceCurrency(){
    const loc=document.getElementById('locationSelect').value;
    const c=currencyForLoc(loc);
    document.getElementById('priceSymbol').textContent=c.sym;
    document.getElementById('wholesaleSymbol').textContent=c.sym;
    document.getElementById('priceLabel').textContent=`Price (${c.code}) *`;
}
updatePriceCurrency();

// ── STORE ROOM / BIN CASCADE ────────────────────────────────────
async function loadStoreRooms(){
    const loc=document.getElementById('locationSelect').value;
    const rs=document.getElementById('storeRoomSelect');
    const bs=document.getElementById('binSelect');
    document.getElementById('storageRoomId').value='';
    document.getElementById('storageShelfId').value='';
    document.getElementById('binLocationHidden').value='';
    document.getElementById('binDisplay').value='';
    bs.innerHTML='<option value="">No specific bin</option>';
    if(!loc){rs.innerHTML='<option value="">Select Location first</option>';return;}
    rs.innerHTML='<option value="">Loading rooms...</option>';
    try{
        const res=await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(loc)}`);
        const data=await res.json();
        if(!data.rooms||!data.rooms.length){rs.innerHTML='<option value="">No rooms for this location</option>';return;}
        rs.innerHTML='<option value="">Select Store Room *</option>'+data.rooms.map(r=>`<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    }catch(e){rs.innerHTML='<option value="">Error loading rooms</option>';}
}

document.getElementById('storeRoomSelect').addEventListener('change',async function(){
    const rid=this.value;
    const bs=document.getElementById('binSelect');
    document.getElementById('storageRoomId').value=rid||'';
    document.getElementById('storageShelfId').value='';
    document.getElementById('binLocationHidden').value='';
    document.getElementById('binDisplay').value='';
    bs.innerHTML='<option value="">No specific bin — room level only</option>';
    if(!rid) return;
    try{
        const res=await fetch(`/admin/storage/shelves-for-room?room_id=${rid}`);
        const data=await res.json();
        if(!data.shelves||!data.shelves.length){bs.innerHTML='<option value="">No bins in this room yet</option>';return;}
        bs.innerHTML='<option value="">No specific bin — room level only</option>'+
            data.shelves.map(s=>`<option value="${s.id}" data-code="${s.full_bin_code}" data-occupied="${s.occupied_by?'1':''}" data-name="${s.occupied_by||''}">`+
            `${s.full_bin_code}${s.occupied_by?' ⚠ OCCUPIED: '+s.occupied_by:' ✓ Empty'}</option>`).join('');
    }catch(e){bs.innerHTML='<option value="">Error loading bins</option>';}
});

let prevBin='';
document.getElementById('binSelect').addEventListener('change',function(){
    const sel=this.options[this.selectedIndex];
    if(!sel||!sel.value){
        // No bin selected — room level storage only
        document.getElementById('storageShelfId').value='';
        document.getElementById('binLocationHidden').value='';
        document.getElementById('binDisplay').value='Room level (no bin)';
        document.getElementById('confirmSharedBin').value='';
        prevBin='';
        return;
    }
    if(sel.dataset.occupied==='1'){
        const ok=confirm(
            `⚠ CAUTION: This bin already contains:\n"${sel.dataset.name}"\n\n`+
            `Sharing a bin groups items together physically.\n`+
            `This is allowed but may affect picking accuracy during stock audits.\n\n`+
            `Click OK to confirm shared bin storage.\nClick Cancel to choose a different bin.`
        );
        if(!ok){this.value=prevBin;return;}
        document.getElementById('confirmSharedBin').value='1';
    } else {
        document.getElementById('confirmSharedBin').value='';
    }
    prevBin=this.value;
    const code=sel.dataset.code||'';
    document.getElementById('storageShelfId').value=this.value;
    document.getElementById('binLocationHidden').value=code;
    document.getElementById('binDisplay').value=code;
});

// ── PHOTO PREVIEW ───────────────────────────────────────────────
function previewPhotos(input){
    const preview=document.getElementById('photoPreview');
    preview.innerHTML='';
    Array.from(input.files).slice(0,10).forEach((file,i)=>{
        const reader=new FileReader();
        reader.onload=e=>{
            const d=document.createElement('div');d.className='relative';
            d.innerHTML=`<img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg border-2 ${i===0?'border-gold':'border-gray-200'}">
                ${i===0?'<div class="absolute -top-1 -right-1 bg-gold text-navy text-[9px] font-700 px-1 rounded">MAIN</div>':''}`;
            preview.appendChild(d);
        };
        reader.readAsDataURL(file);
    });
}
</script>

@endsection
