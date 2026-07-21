{{-- FILE: resources/views/admin/storage/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Storage Rooms')
@section('page-title','Storage Rooms & Bin Locations')
@section('page-sub','Manage store rooms and shelf/bin locations at each city')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

{{-- ── Create new room ──────────────────────────────────────────── --}}
<div class="stat-card mb-6">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Add a Store Room</h2>
  <form method="POST" action="{{ route('admin.storage.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    @csrf
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
      <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
        @foreach($locations as $loc)
          <option value="{{ $loc }}">{{ $loc }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Room Name *</label>
      <input type="text" name="name" required placeholder="e.g. Store 1, Main Warehouse"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Room Code *</label>
      <input type="text" name="code" required placeholder="e.g. ILE-S1"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono uppercase focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">
        + Add Room
      </button>
    </div>
    <div class="sm:col-span-4">
      <input type="text" name="description" placeholder="Optional description / notes about this room"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
  </form>
</div>

{{-- NEW: multi-room label printing — select rooms across DIFFERENT
     location groups (not just within one), and print bin labels for
     every bin in all of them in one batch. Previously only worked for
     bins within a single already-open room. --}}
<div class="flex items-center justify-between mb-4">
  <label class="flex items-center gap-2 text-xs font-body text-gray-500">
    <input type="checkbox" id="selectAllRooms" onchange="toggleSelectAllRooms(this)">
    Select all rooms
  </label>
  <button type="button" id="printSelectedRoomsBtn" onclick="printSelectedRooms()" disabled
    class="text-xs font-body font-700 bg-gray-200 text-gray-400 px-4 py-2 rounded-xl cursor-not-allowed transition-colors">
    🏷 Print Bin Labels for Selected Rooms (0)
  </button>
</div>

{{-- ── Rooms grouped by location ────────────────────────────────── --}}
@forelse($rooms as $location => $locationRooms)
<div class="mb-6">
  <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">{{ $location }}</h3>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($locationRooms as $room)
    <div class="stat-card hover:shadow-md transition-shadow relative">
      <label class="absolute top-3 right-3 z-10 bg-white rounded p-1 shadow-sm" onclick="event.stopPropagation()">
        <input type="checkbox" class="room-select-checkbox" value="{{ $room->id }}" onchange="updatePrintSelectedRoomsButton()">
      </label>
      <a href="{{ route('admin.storage.show', $room->id) }}" class="block">
        <div class="flex items-start justify-between pr-6">
          <div>
            <div class="font-display font-700 text-navy text-base">{{ $room->name }}</div>
            <div class="text-xs font-mono text-gray-400 mt-0.5">{{ $room->code }}</div>
          </div>
          <span class="badge badge-blue">{{ $shelfCounts[$room->id] ?? 0 }} bins</span>
        </div>
        @if($room->description)
          <p class="text-xs text-gray-400 font-body mt-2">{{ $room->description }}</p>
        @endif
      </a>
    </div>
    @endforeach
  </div>
</div>
@empty
<div class="stat-card text-center py-12">
  <div class="text-gray-300 text-5xl mb-3">🏬</div>
  <div class="text-gray-500 font-body text-sm">No store rooms set up yet. Add your first one above.</div>
</div>
@endforelse

<script>
function toggleSelectAllRooms(master) {
    document.querySelectorAll('.room-select-checkbox').forEach(cb => cb.checked = master.checked);
    updatePrintSelectedRoomsButton();
}

function updatePrintSelectedRoomsButton() {
    const checked = document.querySelectorAll('.room-select-checkbox:checked');
    const btn = document.getElementById('printSelectedRoomsBtn');
    btn.textContent = `🏷 Print Bin Labels for Selected Rooms (${checked.length})`;
    if (checked.length > 0) {
        btn.disabled = false;
        btn.className = 'text-xs font-body font-700 bg-gold text-navy px-4 py-2 rounded-xl hover:bg-yellow-500 transition-colors';
    } else {
        btn.disabled = true;
        btn.className = 'text-xs font-body font-700 bg-gray-200 text-gray-400 px-4 py-2 rounded-xl cursor-not-allowed transition-colors';
    }
}

async function printSelectedRooms() {
    const roomIds = Array.from(document.querySelectorAll('.room-select-checkbox:checked')).map(cb => cb.value);
    if (roomIds.length === 0) return;

    const btn = document.getElementById('printSelectedRoomsBtn');
    const originalText = btn.textContent;
    btn.textContent = 'Gathering bins...';
    btn.disabled = true;

    // Gather every bin across all selected rooms — one call per room,
    // combined into a single batch print. Fine for the small number
    // of rooms typically selected at once.
    let allBinIds = [];
    try {
        for (const roomId of roomIds) {
            const res = await fetch(`{{ route('admin.storage.shelves-for-room') }}?room_id=${roomId}`);
            const data = await res.json();
            (data.shelves || []).forEach(s => allBinIds.push(s.id));
        }
    } catch (e) {
        alert('Could not load bins for one or more selected rooms.');
        updatePrintSelectedRoomsButton();
        return;
    }

    if (allBinIds.length === 0 && roomIds.length === 0) {
        alert('Nothing to print.');
        updatePrintSelectedRoomsButton();
        return;
    }

    // FIXED: previously only printed bins, meaning "Rooms Only" filter
    // on the print page showed nothing at all for a batch built this
    // way. Now includes the actual room-level labels too, alongside
    // every bin in them. Rooms with zero bins still get their own
    // room label printed rather than being silently skipped.
    window.open(`{{ route('admin.storage.bin-labels') }}?ids=${allBinIds.join(',')}&room_ids=${roomIds.join(',')}`, '_blank');
    updatePrintSelectedRoomsButton();
}
</script>

@endsection
