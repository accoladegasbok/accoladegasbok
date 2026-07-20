{{-- FILE: resources/views/admin/storage/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $room->name . ' — Bins')
@section('page-title', $room->name)
@section('page-sub', $room->location . ' · Room code: ' . $room->code)

@section('header-actions')
<a href="{{ route('admin.storage.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  ← All Rooms
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

{{-- ── Rename room — admin only ───────────────────────────────────── --}}
@if(session('staff_role') === 'admin')
<div class="stat-card mb-6">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Edit Room (Admin Only)</h2>

  {{-- FIXED: "Delete Room" used to be a <form> nested INSIDE this
       "Edit Room" <form> — browsers don't support nested forms, so the
       parser silently discarded the inner <form> tag and merged its
       hidden _method=DELETE field into THIS outer form (which already
       had its own _method=PUT field). With two _method values in one
       form, the LAST one in the DOM won regardless of which button was
       clicked — meaning "Save Changes" was actually submitting as a
       DELETE request every time. That's exactly why editing an empty
       room deleted it outright, and why a room WITH bins showed the
       delete-safety error even when you were only trying to save.
       Fixed by making these two completely separate, sibling forms. --}}
  <form method="POST" action="{{ route('admin.storage.update', $room->id) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    @csrf @method('PUT')
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Room Name</label>
      <input type="text" name="name" value="{{ old('name', $room->name) }}" required
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Room Code</label>
      <input type="text" name="code" value="{{ old('code', $room->code) }}" required
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400">
    </div>
    <div class="sm:col-span-2">
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Description</label>
      <input type="text" name="description" value="{{ old('description', $room->description) }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div class="sm:col-span-4">
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Full Street Address</label>
      <input type="text" name="address" value="{{ old('address', $room->address) }}" placeholder="e.g. 3230 S Hwy 77, Suite 303, Waxahachie TX 75165"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      <p class="text-xs text-gray-400 mt-1">Required for Stock Transfers — shown to the receiving agent to confirm the correct destination before accepting.</p>
    </div>
    <div class="sm:col-span-4">
      <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-6 py-2.5 rounded-xl hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
    </div>
  </form>

  {{-- Now a completely separate form, not nested inside the one above --}}
  <div class="flex justify-end mt-3">
    <form method="POST" action="{{ route('admin.storage.destroy', $room->id) }}"
          onsubmit="return confirm('Delete this entire room? Only possible if it has zero bins remaining.')">
      @csrf @method('DELETE')
      <button type="submit" class="text-xs font-body border border-red-200 text-red-500 hover:bg-red-50 px-4 py-2.5 rounded-xl transition-colors">
        Delete Room
      </button>
    </form>
  </div>

  <p class="text-xs text-gray-400 font-body mt-2">Changing the room code does NOT rename existing bin codes already using the old code — only newly generated bins will use the new code. Deleting a room requires zero bins remaining inside it.</p>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

  {{-- ── Bulk generate grid ───────────────────────────────────────── --}}
  <div class="stat-card">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Bulk Generate Bins</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Quickly create a whole shelf grid at once — e.g. shelves A–F, 10 columns, 4 spaces each = 240 bins.</p>
    <form method="POST" action="{{ route('admin.storage.shelves.bulk', $room->id) }}" class="space-y-3">
      @csrf
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Shelf Codes (comma-separated)</label>
        <input type="text" name="shelf_codes" required placeholder="e.g. A,B,C,D,E,F"
          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono uppercase focus:outline-none focus:border-yellow-400">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Columns per Shelf</label>
          <input type="number" name="columns" required min="1" max="99" placeholder="e.g. 10"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Spaces per Column</label>
          <input type="number" name="spaces" required min="1" max="99" placeholder="e.g. 4"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
      <button type="submit" class="w-full bg-navy text-white font-display font-700 text-sm py-3 rounded-xl hover:bg-navy-light transition-colors">
        Generate Bins
      </button>
    </form>
  </div>

  {{-- ── Add single bin ──────────────────────────────────────────── --}}
  <div class="stat-card">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Add a Single Bin</h2>
    <p class="text-xs text-gray-400 font-body mb-4">For one-off bins that don't fit the regular grid pattern.</p>
    <form method="POST" action="{{ route('admin.storage.shelves.add', $room->id) }}" class="space-y-3">
      @csrf
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Shelf *</label>
          <input type="text" name="shelf_code" required placeholder="A"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono uppercase focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Column</label>
          <input type="number" name="column_number" min="0" placeholder="01"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Space</label>
          <input type="number" name="space_number" min="0" placeholder="02"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
      <input type="text" name="notes" placeholder="Optional notes (e.g. heavy items only)"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
      <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">
        + Add Bin
      </button>
    </form>
  </div>

</div>

{{-- ── Bins list ────────────────────────────────────────────────── --}}
<div class="stat-card overflow-hidden p-0">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Bins in this Room</h2>
    <div class="flex items-center gap-3">
      <span class="text-xs text-gray-400 font-body">{{ $shelves->count() }} total</span>
      {{-- NEW: multi-select print — the backend (BinLabelController::batch())
           already supported ?ids=1,2,3, it just had no checkbox UI to
           actually use it for printing several DIFFERENT bins on one
           print run, rather than one bin repeated 3 times. --}}
      <button type="button" id="printSelectedBtn" onclick="printSelectedBins()" disabled
        class="text-xs font-body font-700 bg-gray-200 text-gray-400 px-3 py-1.5 rounded-lg cursor-not-allowed transition-colors">
        🏷 Print Selected (0)
      </button>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="px-4 py-3">
            <input type="checkbox" id="selectAllBins" onchange="toggleSelectAll(this)">
          </th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Bin Code</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Shelf</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Column</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Space</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Parts Stored</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($shelves as $shelf)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3">
            <input type="checkbox" class="bin-select-checkbox" value="{{ $shelf->id }}" onchange="updatePrintSelectedButton()">
          </td>
          <td class="px-4 py-3 font-mono font-700 text-navy">{{ $shelf->full_bin_code }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->shelf_code }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->column_number ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->space_number ?? '—' }}</td>
          <td class="px-4 py-3">
            @php $count = $partsCount[$shelf->id] ?? 0; @endphp
            <span class="badge {{ $count > 0 ? 'badge-blue' : 'badge-gray' }}">{{ $count }} part{{ $count !== 1 ? 's' : '' }}</span>
          </td>
          <td class="px-4 py-3 text-right">
            <button type="button" onclick="toggleBinEdit({{ $shelf->id }})"
               class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors mr-1">
              ✎ Edit
            </button>
            <button type="button" onclick="toggleRelocate({{ $shelf->id }})"
               class="text-xs font-body border border-blue-200 text-blue-500 hover:border-blue-400 hover:text-blue-700 px-3 py-1.5 rounded-lg transition-colors mr-1">
              ↔ Relocate Items
            </button>
            <a href="{{ route('admin.storage.shelves.barcode', $shelf->id) }}" target="_blank"
               class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors mr-1">
              🏷 Barcode
            </a>
            <form method="POST" action="{{ route('admin.storage.shelves.destroy', $shelf->id) }}"
                  onsubmit="return confirm('Delete bin {{ $shelf->full_bin_code }}?')" class="inline">
              @csrf @method('DELETE')
              <button type="submit" class="text-xs font-body border border-red-200 text-red-400 hover:border-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg transition-colors">
                Delete
              </button>
            </form>
          </td>
        </tr>
        {{-- FIXED: bins could only be Added or Deleted before — this
             lets you rename/renumber THIS bin in place. It does NOT
             move the bin anywhere — it stays in this room. To move
             what's stored IN this bin to a different bin, use
             "Relocate Items" below instead — that's a deliberately
             separate action. --}}
        <tr id="bin-edit-row-{{ $shelf->id }}" class="hidden bg-blue-50 border-b border-gray-100">
          <td colspan="7" class="px-4 py-3">
            <form method="POST" action="{{ route('admin.storage.shelves.update', $shelf->id) }}" class="grid grid-cols-2 sm:grid-cols-5 gap-2 items-end">
              @csrf @method('PUT')
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Shelf Code</label>
                <input type="text" name="shelf_code" value="{{ $shelf->shelf_code }}" required
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-mono uppercase focus:outline-none focus:border-gold">
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Column</label>
                <input type="number" name="column_number" value="{{ $shelf->column_number }}" min="0"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-gold">
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Space</label>
                <input type="number" name="space_number" value="{{ $shelf->space_number }}" min="0"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-gold">
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Notes</label>
                <input type="text" name="notes" value="{{ $shelf->notes ?? '' }}"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-gold">
              </div>
              <div class="flex gap-1">
                <button type="submit" class="flex-1 bg-gold text-navy font-700 text-xs py-1.5 rounded-lg hover:bg-yellow-500 transition-colors">Save</button>
                <button type="button" onclick="toggleBinEdit({{ $shelf->id }})" class="text-xs text-gray-400 px-2 hover:text-gray-600">Cancel</button>
              </div>
            </form>
            <p class="text-[10px] text-gray-400 mt-1.5">This renames the bin itself — it stays in {{ $room->name }}. It does not move any parts or the bin's location.</p>
          </td>
        </tr>
        {{-- NEW: relocate everything currently sitting in this bin to
             a different, already-existing bin — same room, another
             shelf, or a different room at this location. This bin
             itself is untouched; it just ends up empty afterward. --}}
        <tr id="relocate-row-{{ $shelf->id }}" class="hidden bg-amber-50 border-b border-gray-100">
          <td colspan="7" class="px-4 py-3">
            <form method="POST" action="{{ route('admin.storage.shelves.relocate-items', $shelf->id) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2 items-end">
              @csrf
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Target Room</label>
                <select id="relocate-room-select-{{ $shelf->id }}" onchange="loadTargetBins({{ $shelf->id }})"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs bg-white focus:outline-none focus:border-gold">
                  <option value="">Select room…</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wider mb-1">Target Bin *</label>
                <select name="target_shelf_id" id="relocate-bin-select-{{ $shelf->id }}" required
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs bg-white focus:outline-none focus:border-gold">
                  <option value="">Select room first</option>
                </select>
              </div>
              <div class="sm:col-span-2 flex gap-1">
                <button type="submit" class="flex-1 bg-navy text-white font-700 text-xs py-1.5 rounded-lg hover:bg-opacity-90 transition-colors"
                        onclick="return confirm('Move every part in {{ $shelf->full_bin_code }} to the selected bin? This bin will end up empty.')">
                  Relocate Items
                </button>
                <button type="button" onclick="toggleRelocate({{ $shelf->id }})" class="text-xs text-gray-400 px-2 hover:text-gray-600">Cancel</button>
              </div>
            </form>
            <p class="text-[10px] text-gray-500 mt-1.5">Moves every part currently in <strong>{{ $shelf->full_bin_code }}</strong> to the bin you choose. {{ $shelf->full_bin_code }} itself is not touched — it just becomes empty and ready for new stock.</p>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No bins yet — use the forms above to create some.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
const ROOM_LOCATION = @json($room->location);
const CURRENT_ROOM_ID = {{ $room->id }};
const loadedRelocateRooms = {};

function toggleBinEdit(shelfId) {
    document.getElementById('bin-edit-row-' + shelfId).classList.toggle('hidden');
}

function toggleSelectAll(masterCheckbox) {
    document.querySelectorAll('.bin-select-checkbox').forEach(cb => cb.checked = masterCheckbox.checked);
    updatePrintSelectedButton();
}

function updatePrintSelectedButton() {
    const checked = document.querySelectorAll('.bin-select-checkbox:checked');
    const btn = document.getElementById('printSelectedBtn');
    btn.textContent = `🏷 Print Selected (${checked.length})`;
    if (checked.length > 0) {
        btn.disabled = false;
        btn.className = 'text-xs font-body font-700 bg-gold text-navy px-3 py-1.5 rounded-lg hover:bg-yellow-500 transition-colors';
    } else {
        btn.disabled = true;
        btn.className = 'text-xs font-body font-700 bg-gray-200 text-gray-400 px-3 py-1.5 rounded-lg cursor-not-allowed transition-colors';
    }
}

function printSelectedBins() {
    const ids = Array.from(document.querySelectorAll('.bin-select-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;
    window.open(`{{ route('admin.storage.bin-labels') }}?ids=${ids.join(',')}`, '_blank');
}

async function toggleRelocate(shelfId) {
    const row = document.getElementById('relocate-row-' + shelfId);
    row.classList.toggle('hidden');
    if (!row.classList.contains('hidden') && !loadedRelocateRooms[shelfId]) {
        loadedRelocateRooms[shelfId] = true;
        const roomSelect = document.getElementById('relocate-room-select-' + shelfId);
        try {
            const res = await fetch(`{{ route('admin.storage.rooms-for-location') }}?location=${encodeURIComponent(ROOM_LOCATION)}`);
            const data = await res.json();
            roomSelect.innerHTML = '<option value="">Select room…</option>' +
                (data.rooms || []).map(r => `<option value="${r.id}" ${r.id === CURRENT_ROOM_ID ? 'selected' : ''}>${r.name} (${r.code})</option>`).join('');
            if (roomSelect.value) loadTargetBins(shelfId);
        } catch (e) {
            roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
        }
    }
}

async function loadTargetBins(shelfId) {
    const roomId = document.getElementById('relocate-room-select-' + shelfId).value;
    const binSelect = document.getElementById('relocate-bin-select-' + shelfId);
    if (!roomId) { binSelect.innerHTML = '<option value="">Select room first</option>'; return; }
    binSelect.innerHTML = '<option value="">Loading…</option>';
    try {
        const res = await fetch(`{{ route('admin.storage.shelves-for-room') }}?room_id=${roomId}`);
        const data = await res.json();
        binSelect.innerHTML = '<option value="">Select target bin…</option>' +
            (data.shelves || []).filter(s => s.id != shelfId).map(s => `<option value="${s.id}">${s.full_bin_code}</option>`).join('');
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
}
</script>

@endsection
