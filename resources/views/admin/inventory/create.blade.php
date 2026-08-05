{{-- FILE: resources/views/admin/inventory/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Part')
@section('page-title','Add Part Manually')
@section('page-sub','Enter a part directly without using the harvest checklist')

@section('content')
<div class="max-w-2xl">
  <form method="POST" action="{{ route('admin.inventory.store') }}">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Vehicle</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Brand *</label>
          <select name="brand" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select brand</option>
            @foreach($brands as $b)<option value="{{ $b }}" {{ old('brand')===$b?'selected':'' }}>{{ $b }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
          <input type="text" name="model" value="{{ old('model') }}" required placeholder="Camry, Accord, Altima..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year From *</label>
          <input type="number" name="year_from" value="{{ old('year_from') }}" required min="1990" max="{{ date('Y')+1 }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year To *</label>
          <input type="number" name="year_to" value="{{ old('year_to') }}" required min="1990" max="{{ date('Y')+1 }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
    </div>

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Part Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Part Name *</label>
          @if(session('staff_role') === 'admin')
            <input type="text" name="part_name" value="{{ old('part_name') }}" required list="partNameDatalist" placeholder="Select a standard name, or type a new one"
              class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
            <datalist id="partNameDatalist">
              @foreach(\App\Data\PartNames::flat() as $pn)
                <option value="{{ $pn }}"></option>
              @endforeach
            </datalist>
            <p class="text-xs text-gray-400 font-body mt-1">As admin, you can type a new name if it's not listed — this keeps naming consistent for everyone else.</p>
          @else
            <select name="part_name" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
              <option value="">Select a standard part name</option>
              @foreach(\App\Data\PartNames::flat() as $pn)
                <option value="{{ $pn }}" {{ old('part_name')===$pn?'selected':'' }}>{{ $pn }}</option>
              @endforeach
            </select>
            <p class="text-xs text-gray-400 font-body mt-1">Only admin can add a name not on this list, to keep naming uniform.</p>
          @endif
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Category *</label>
          <select name="part_category" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select category</option>
            @foreach($categories as $c)<option value="{{ $c }}" {{ old('part_category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Side</label>
          <select name="side" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="N/A">N/A</option>
            <option value="D/S">D/S (Driver Side)</option>
            <option value="P/S">P/S (Passenger Side)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5" id="priceLabel">Price (Select Location) *</label>
          <div class="relative">
            <span id="priceSymbol" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-mono text-gray-400">—</span>
            <input type="number" name="price_usd" id="priceInput" value="{{ old('price_usd') }}" step="0.01" min="0" required
              class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
          </div>
          <p class="text-xs text-gray-400 font-body mt-1">Price is fixed in this location's currency — no conversion happens later.</p>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Grade *</label>
          <select name="condition_grade" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach(['A'=>'A — Like New','B'=>'B — Good','C'=>'C — Fair','New'=>'New OEM'] as $v=>$l)
              <option value="{{ $v }}" {{ old('condition_grade')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
          <select name="location" id="locationSelect" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach($locations as $l)<option value="{{ $l }}" {{ old('location')===$l?'selected':'' }}>{{ $l }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Store Room</label>
          <select id="storeRoomSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select Location first</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Bin</label>
          <select id="binSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select Store Room first</option>
          </select>
          <input type="hidden" name="storage_shelf_id" id="storageShelfIdInput">
          <input type="hidden" name="bin_location" id="binLocationInput">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">OEM Part Number <span class="text-gray-400 font-normal normal-case">(primary)</span></label>
          <input type="text" name="oem_part_number" value="{{ old('oem_part_number') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400">
        </div>

        {{-- NEW: additional OEM numbers, if more than one is already
             known at entry time (e.g. Denso AND Aisin both make this
             alternator) — optional, most parts only need the primary
             field above. --}}
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-2">
            Additional OEM Numbers <span class="text-gray-400 normal-case font-400">(optional)</span>
          </label>
          <div id="extraOemNumbers" class="space-y-2"></div>
          <button type="button" onclick="addOemNumberRow()"
            class="mt-2 text-xs font-body font-500 text-blue-600 hover:text-blue-800 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add another OEM number
          </button>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage</label>
          <input type="number" name="mileage" value="{{ old('mileage') }}" min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Colour</label>
          <input type="text" name="colour" value="{{ old('colour') }}" placeholder="White, Silver..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Description</label>
          <textarea name="description" rows="2"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('description') }}</textarea>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Add to Inventory
      </button>
      <a href="{{ route('admin.inventory.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>

@push('scripts')
<script>
// ── Price currency by location — fixed, no conversion ever ────────────────
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
});

// ── Additional OEM numbers — repeatable rows, submitted with the
// main form since the part doesn't have an ID yet at creation time. ──
let oemRowIndex = 0;
function addOemNumberRow() {
    const container = document.getElementById('extraOemNumbers');
    const idx = oemRowIndex++;
    const div = document.createElement('div');
    div.className = 'flex gap-2 oem-row';
    div.innerHTML = `
        <input type="text" name="oem_numbers[${idx}][number]" placeholder="OEM number"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs font-body font-mono focus:outline-none focus:border-yellow-400">
        <input type="text" name="oem_numbers[${idx}][manufacturer]" placeholder="Manufacturer (optional)"
            class="w-40 border border-gray-200 rounded-lg px-3 py-2 text-xs font-body focus:outline-none focus:border-yellow-400">
        <button type="button" onclick="this.closest('.oem-row').remove()" class="text-red-400 hover:text-red-600 px-2 flex-shrink-0">✕</button>
    `;
    container.appendChild(div);
}
</script>
@endpush
@endsection
