{{-- FILE: resources/views/admin/inventory/consumable-create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Consumable')
@section('page-title','Add Consumable Item')
@section('page-sub','Oils, fluids, filters, electronics, computers & other non-vehicle-specific items')
@section('header-actions')
<a href="{{ route('admin.inventory.index') }}"
   class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  Cancel
</a>
@endsection
@section('content')

@if($errors->any())
<div class="bg-red-50 border border-red-300 rounded-2xl px-5 py-4 mb-5 max-w-2xl">
    <div class="font-700 text-red-700 text-sm mb-2">⚠ Please fix the following:</div>
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)
        <li class="text-sm text-red-600">{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

@php $flatItems = collect($grouped ?? [])->flatten(1)->values(); @endphp

<form method="POST" action="{{ route('admin.inventory.consumable.store') }}" class="max-w-3xl">
  @csrf
  <input type="hidden" name="existing_id" id="existingId" value="{{ old('existing_id') }}" disabled>

  {{-- Mode: product already in the system, or a brand-new product --}}
  <div class="flex gap-2 mb-5">
    <button type="button" id="tabExisting" onclick="setMode('existing')" class="flex-1 font-display font-700 text-sm py-3 rounded-xl border-2 border-gray-200 bg-white text-navy">Already in stock - add a batch / new location</button>
    <button type="button" id="tabNew" onclick="setMode('new')" class="flex-1 font-display font-700 text-sm py-3 rounded-xl border-2 border-gray-200 bg-white text-navy">New product (first time)</button>
  </div>

  {{-- Pick an existing product: photo, name, stock - nothing is typed again --}}
  <div id="existingCard" class="hidden bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Pick the product</h2>
    <div class="flex gap-2 mb-3">
      <input type="text" id="pickQ" placeholder="Search products already added..." class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
      <select id="pickCat" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All categories</option>
        @foreach(array_keys($grouped ?? []) as $c)<option>{{ $c }}</option>@endforeach
      </select>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-[420px] overflow-y-auto pr-1">
      @foreach($flatItems as $it)
      <button type="button" data-id="{{ $it['id'] }}" data-cat="{{ $it['category'] }}" data-text="{{ strtolower($it['name'].' '.$it['brand'].' '.$it['code']) }}"
              class="pick text-left border-2 border-gray-100 rounded-xl overflow-hidden bg-white hover:border-yellow-400 transition-colors">
        <div class="aspect-square bg-white flex items-center justify-center">
          @if($it['photo'])<img src="{{ $it['photo'] }}" class="w-full h-full object-contain" loading="lazy" alt="">@else<span class="text-[10px] text-gray-300">No photo yet</span>@endif
        </div>
        <div class="p-2">
          <div class="text-xs font-700 text-navy leading-tight">{{ $it['name'] }}</div>
          <div class="text-[10px] text-gray-400">{{ $it['brand'] }}{{ $it['size'] ? ' · '.$it['size'] : '' }}</div>
          <div class="text-[10px] text-gray-500 mt-0.5">{{ $it['code'] }} &middot; {{ $it['total'] }} in stock</div>
        </div>
      </button>
      @endforeach
      @if($flatItems->isEmpty())<div class="col-span-full text-sm text-gray-400 py-6 text-center">Nothing added yet - use "New product".</div>@endif
    </div>
    <div id="pickedBox" class="mt-4 text-sm text-gray-400">Select a product to see its stock at every location.</div>
  </div>

  <div id="productCard" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Product Details <span class="normal-case font-normal text-gray-400">(new product - type once)</span></h2>

    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="relative">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Brand *</label>
        <input type="text" id="brandTypeahead" autocomplete="off" placeholder="Type or pick a brand..." required
          value="{{ old('other_brand', old('brand')) }}"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
        <input type="hidden" name="brand" id="brandHidden" value="{{ old('brand') }}">
        <input type="hidden" name="other_brand" id="otherBrandHidden" value="{{ old('other_brand') }}">
        <div id="brandSuggestions" class="hidden absolute bg-white border border-gray-200 rounded-lg shadow-lg z-50 w-full mt-1 max-h-48 overflow-y-auto"></div>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Category *</label>
        <select name="part_category" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          @foreach(['Consumable','Electronics','Computers','Other'] as $cat)
          <option value="{{ $cat }}" {{ old('part_category','Consumable') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="col-span-2">
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Product Name *</label>
        <input type="text" name="part_name" required value="{{ old('part_name') }}" placeholder="e.g. 5W-30 Synthetic Engine Oil, Laptop Motherboard, Electric Motor"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Size / Volume (optional)</label>
        <input type="text" name="unit_size" value="{{ old('unit_size') }}" placeholder="e.g. 5L, 1 Quart, 4-pack"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>

    <div>
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Compatibility Note (optional)</label>
      <input type="text" name="compatibility_note" value="{{ old('compatibility_note') }}" placeholder="e.g. Suitable for most 4-cylinder Toyota/Honda engines"
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Pricing</h2>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Price *</label>
        <input type="number" name="price_usd" step="0.01" min="0" required value="{{ old('price_usd') }}" placeholder="0.00"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Condition</label>
        <div class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-body text-navy font-500">&#10022; Brand new</div>
        <input type="hidden" name="condition_grade" value="New">
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Location & Stock</h2>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
        <select name="location" id="locationSelect" required onchange="loadStoreRooms()"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Location</option>
          @foreach($locations as $loc)
            <option value="{{ $loc }}" {{ old('location')===$loc?'selected':'' }}>{{ $loc }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Store Room *</label>
        <select id="storeRoomSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">Select Location first</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Bin <span class="text-gray-400 font-normal">(optional)</span></label>
        <select id="binSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="">No specific bin</option>
        </select>
        <input type="hidden" name="storage_shelf_id" id="storageShelfId" value="{{ old('storage_shelf_id') }}">
        <input type="hidden" name="confirm_shared_bin" id="confirmSharedBin" value="">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1" id="qtyLbl">Stock Quantity</label>
        <input type="number" name="stock_qty" value="{{ old('stock_qty', 1) }}" min="1"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">Room is required. Bin is optional — items can be stored at room level and assigned a bin later.</p>
    <div class="mt-4">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Supplier invoice / lot (optional)</label>
      <input type="text" name="supplier_ref" maxlength="80" value="{{ old('supplier_ref') }}" placeholder="Shown on the batch history"
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Notes</h2>
    <textarea name="description" rows="3" placeholder="Any additional notes about this item...">{{ old('description') }}</textarea>
  </div>

  <div class="flex gap-3 justify-end pb-8">
    <a href="{{ route('admin.inventory.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      <span id="submitLbl">Add Item</span>
    </button>
  </div>

</form>

<script>
// Brands safe to write directly to the ENUM `brand` column in the DB.
// Must exactly mirror database/migrations/2026_06_17_000004_expand_brand_enum_consumables.php
const ENUM_SAFE_BRANDS = ['Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz','Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda','Mobil 1','Castrol','Valvoline','Shell','Fram','Bosch','Denso','NGK','ACDelco'];

// Suggestion-only brands — shown in the typeahead for convenience but
// NEVER written directly to the brand column (fixed ENUM would reject
// them). These always route through brand='Generic' with the real
// name folded into part_name, same as any other unlisted/custom brand.
const SUGGESTED_ONLY_BRANDS = ['Dell','HP','Lenovo','Samsung','LG', @foreach($customBrands as $cb)'{{ addslashes($cb) }}',@endforeach];

const KNOWN_BRANDS = [...ENUM_SAFE_BRANDS, ...SUGGESTED_ONLY_BRANDS]; // suggestions dropdown only

const brandTypeahead   = document.getElementById('brandTypeahead');
const brandHidden      = document.getElementById('brandHidden');
const otherBrandHidden = document.getElementById('otherBrandHidden');
const brandSuggestions = document.getElementById('brandSuggestions');

function setBrandValue(typedValue) {
    const enumMatch = ENUM_SAFE_BRANDS.find(b => b.toLowerCase() === typedValue.toLowerCase());
    if (enumMatch) {
        brandHidden.value      = enumMatch;
        otherBrandHidden.value = '';
    } else if (typedValue.trim()) {
        brandHidden.value      = 'Generic';
        otherBrandHidden.value = typedValue.trim();
    } else {
        brandHidden.value      = '';
        otherBrandHidden.value = '';
    }
}

function renderBrandSuggestions(query) {
    const q = query.toLowerCase();
    const matches = KNOWN_BRANDS.filter(b => b.toLowerCase().includes(q));
    if (matches.length === 0) {
        brandSuggestions.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400 italic">No match — "${query}" will be saved as a new brand.</div>`;
    } else {
        brandSuggestions.innerHTML = matches.map(b =>
            `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm" onclick="selectBrand('${b}')">${b}</div>`
        ).join('');
    }
    brandSuggestions.classList.remove('hidden');
}

function selectBrand(brand) {
    brandTypeahead.value = brand;
    setBrandValue(brand);
    brandSuggestions.classList.add('hidden');
}

brandTypeahead.addEventListener('focus', function() { renderBrandSuggestions(this.value); });
brandTypeahead.addEventListener('input', function() { renderBrandSuggestions(this.value); setBrandValue(this.value); });
brandTypeahead.addEventListener('blur', function() { setTimeout(() => brandSuggestions.classList.add('hidden'), 150); });

// Pre-fill hidden brand fields if returning after validation error
if (brandTypeahead.value) setBrandValue(brandTypeahead.value);

// ── Store Room / Bin cascade ─────────────────────────────────────
async function loadStoreRooms() {
    const loc = document.getElementById('locationSelect').value;
    const rs  = document.getElementById('storeRoomSelect');
    const bs  = document.getElementById('binSelect');
    document.getElementById('storageShelfId').value = '';
    bs.innerHTML = '<option value="">No specific bin</option>';
    if (!loc) { rs.innerHTML = '<option value="">Select Location first</option>'; return; }
    rs.innerHTML = '<option value="">Loading rooms...</option>';
    try {
        const res  = await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        if (!data.rooms || !data.rooms.length) { rs.innerHTML = '<option value="">No rooms for this location</option>'; return; }
        rs.innerHTML = '<option value="">Select Store Room *</option>' + data.rooms.map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    } catch (e) { rs.innerHTML = '<option value="">Error loading rooms</option>'; }
}

document.getElementById('storeRoomSelect').addEventListener('change', async function() {
    const rid = this.value;
    const bs  = document.getElementById('binSelect');
    document.getElementById('storageShelfId').value = '';
    bs.innerHTML = '<option value="">No specific bin — room level only</option>';
    if (!rid) return;
    try {
        const res  = await fetch(`/admin/storage/shelves-for-room?room_id=${rid}`);
        const data = await res.json();
        if (!data.shelves || !data.shelves.length) { bs.innerHTML = '<option value="">No bins in this room yet</option>'; return; }
        bs.innerHTML = '<option value="">No specific bin — room level only</option>' +
            data.shelves.map(s => `<option value="${s.id}" data-occupied="${s.occupied_by?'1':''}" data-name="${s.occupied_by||''}">${s.full_bin_code}${s.occupied_by?' ⚠ OCCUPIED: '+s.occupied_by:' ✓ Empty'}</option>`).join('');
    } catch (e) { bs.innerHTML = '<option value="">Error loading bins</option>'; }
});

let prevBin = '';
document.getElementById('binSelect').addEventListener('change', function() {
    const sel = this.options[this.selectedIndex];
    if (!sel || !sel.value) {
        document.getElementById('storageShelfId').value = '';
        document.getElementById('confirmSharedBin').value = '';
        prevBin = '';
        return;
    }
    if (sel.dataset.occupied === '1') {
        const ok = confirm(`⚠ CAUTION: This bin already contains:\n"${sel.dataset.name}"\n\nSharing a bin groups items together physically.\n\nClick OK to confirm shared bin storage.\nClick Cancel to choose a different bin.`);
        if (!ok) { this.value = prevBin; return; }
        document.getElementById('confirmSharedBin').value = '1';
    } else {
        document.getElementById('confirmSharedBin').value = '';
    }
    prevBin = this.value;
    document.getElementById('storageShelfId').value = this.value;
});

// ── Already in stock / new product ───────────────────────────────
const PICK_ITEMS = @json($flatItems->keyBy('id'));
const SYM = {NGN:'₦', GHS:'GH₵', USD:'$'};
let mode = 'new';
function setMode(m) {
    mode = m;
    const ex = m === 'existing';
    document.getElementById('existingCard').classList.toggle('hidden', !ex);
    document.getElementById('productCard').classList.toggle('hidden', ex);
    // disabled fields are not validated or submitted; the server uses the picked product's stored details
    document.querySelectorAll('#productCard input, #productCard select').forEach(el => el.disabled = ex);
    document.getElementById('existingId').disabled = !ex;
    document.getElementById('tabExisting').classList.toggle('border-yellow-400', ex);
    document.getElementById('tabNew').classList.toggle('border-yellow-400', !ex);
    document.getElementById('qtyLbl').textContent = ex ? 'Quantity in this batch' : 'Stock Quantity';
    document.getElementById('submitLbl').textContent = ex ? 'Add Batch' : 'Add Item';
}
function pickItem(id) {
    document.getElementById('existingId').value = id;
    document.querySelectorAll('.pick').forEach(b => b.classList.toggle('border-yellow-400', b.dataset.id === String(id)));
    showPicked();
}
function showPicked() {
    const it = PICK_ITEMS[document.getElementById('existingId').value], box = document.getElementById('pickedBox');
    if (!it) return;
    let h = '<div class="font-700 text-navy">' + it.name + ' <span class="text-gray-400 font-normal">' + it.code + '</span></div>';
    Object.entries(it.locations).forEach(([l, q]) => { h += '<div class="flex justify-between text-xs py-0.5 max-w-xs"><span>' + l + '</span><b>' + q + '</b></div>'; });
    h += '<div class="flex justify-between text-sm border-t border-gray-100 mt-1 pt-1 max-w-xs"><span>Total</span><b>' + it.total + '</b></div>';
    const loc = document.getElementById('locationSelect').value, p = it.prices[loc] || Object.values(it.prices)[0];
    if (p) {
        const priceIn = document.querySelector('input[name=price_usd]');
        if (!priceIn.value || priceIn.dataset.auto) { priceIn.value = p.price; priceIn.dataset.auto = '1'; }
        h += '<div class="text-[11px] text-gray-400 mt-2">' + (it.prices[loc] ? 'Stocked here already - the price stays as it is.' : 'New location - set its selling price below.') + '</div>';
    }
    box.innerHTML = h;
}
document.querySelectorAll('.pick').forEach(b => b.addEventListener('click', () => pickItem(b.dataset.id)));
document.getElementById('locationSelect').addEventListener('change', () => { if (mode === 'existing') showPicked(); });
function filterPick() {
    const q = document.getElementById('pickQ').value.toLowerCase().trim(), c = document.getElementById('pickCat').value;
    document.querySelectorAll('.pick').forEach(b => { b.style.display = ((!q || b.dataset.text.includes(q)) && (!c || b.dataset.cat === c)) ? '' : 'none'; });
}
document.getElementById('pickQ').addEventListener('input', filterPick);
document.getElementById('pickCat').addEventListener('change', filterPick);
document.querySelector('form').addEventListener('submit', e => {
    if (mode === 'existing' && !document.getElementById('existingId').value) { e.preventDefault(); alert('Pick a product first.'); }
});
setMode({!! old('existing_id') ? "'existing'" : "'new'" !!});
if (document.getElementById('existingId').value) pickItem(document.getElementById('existingId').value);

// Re-load rooms if location was pre-filled from old() after a validation error
if (document.getElementById('locationSelect').value) loadStoreRooms();
</script>

@endsection
