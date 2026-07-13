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

<form method="POST" action="{{ route('admin.inventory.consumable.store') }}" class="max-w-2xl">
  @csrf

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Product Details</h2>

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
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-5">Pricing & Condition</h2>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Price *</label>
        <input type="number" name="price_usd" step="0.01" min="0" required value="{{ old('price_usd') }}" placeholder="0.00"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
      <div>
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Condition *</label>
        <select name="condition_grade" required
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
          <option value="New" {{ old('condition_grade','New')==='New'?'selected':'' }}>New</option>
          <option value="A" {{ old('condition_grade')==='A'?'selected':'' }}>A — Like New</option>
          <option value="B" {{ old('condition_grade')==='B'?'selected':'' }}>B — Good</option>
          <option value="C" {{ old('condition_grade')==='C'?'selected':'' }}>C — Functional</option>
        </select>
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
        <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Stock Quantity</label>
        <input type="number" name="stock_qty" value="{{ old('stock_qty', 1) }}" min="1"
          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold">
      </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">Room is required. Bin is optional — items can be stored at room level and assigned a bin later.</p>
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
      Add Item
    </button>
  </div>

</form>

<script>
const KNOWN_BRANDS = ['Mobil 1','Castrol','Valvoline','Shell','Fram','Bosch','Denso','NGK','ACDelco','Dell','HP','Lenovo','Samsung','LG','Generic'];

const brandTypeahead   = document.getElementById('brandTypeahead');
const brandHidden      = document.getElementById('brandHidden');
const otherBrandHidden = document.getElementById('otherBrandHidden');
const brandSuggestions = document.getElementById('brandSuggestions');

function setBrandValue(typedValue) {
    const match = KNOWN_BRANDS.find(b => b.toLowerCase() === typedValue.toLowerCase());
    if (match) {
        brandHidden.value      = match;
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

// Re-load rooms if location was pre-filled from old() after a validation error
if (document.getElementById('locationSelect').value) loadStoreRooms();
</script>

@endsection
