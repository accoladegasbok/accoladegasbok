{{-- FILE: resources/views/admin/audit/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Start New Audit')
@section('page-title','Start New Audit')
@section('page-sub','Choose a location and category to begin counting')
@section('content')

@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-5">
  {{ session('error') }}
</div>
@endif

<form method="POST" action="{{ route('admin.audit.store') }}" class="max-w-xl">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <div class="mb-4">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
      <select name="location" id="locationSelect" onchange="loadRooms()" required
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
        <option value="">Select Location</option>
        @foreach($locations as $loc)
          <option value="{{ $loc }}" {{ $selectedLocation === $loc ? 'selected' : '' }}>{{ $loc }}</option>
        @endforeach
      </select>
    </div>

    {{-- FIXED: audit could only be scoped to Location + Category before
         — no way to audit a single storage room at a time. Room is
         optional; leaving it blank keeps the old "whole location"
         behavior, so nothing breaks for that workflow. --}}
    <div class="mb-4">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">
        Storage Room <span class="font-normal normal-case text-gray-300">(optional — leave blank to audit the whole location)</span>
      </label>
      <select name="room_id" id="roomSelect"
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
        <option value="">Whole location (all rooms)</option>
        @foreach($rooms as $room)
          <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->code }})</option>
        @endforeach
      </select>
    </div>

    {{-- FIXED: category used to be a single required dropdown — now
         multi-select checkboxes, with none checked meaning "all
         categories" (the "all category option should be available
         too" request). --}}
    <div>
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-2">
        Part Categories <span class="font-normal normal-case text-gray-300">(leave all unchecked for every category)</span>
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        @foreach($categories as $cat)
        <label class="flex items-center gap-2 text-xs font-body text-gray-600 border border-gray-200 rounded-lg px-2.5 py-2 cursor-pointer hover:border-gold transition-colors">
          <input type="checkbox" name="category[]" value="{{ $cat }}" class="rounded border-gray-300 text-gold focus:ring-gold">
          {{ $cat === 'Consumable' ? 'Consumable (Oils/Fluids)' : $cat }}
        </label>
        @endforeach
      </div>
    </div>
  </div>

  <p class="text-xs text-gray-400 mb-4">
    This will create a count sheet of every available part matching this location, room, and category selection, snapshotting the system's expected quantities at this moment.
  </p>

  <div class="flex gap-3 justify-end">
    <a href="{{ route('admin.audit.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Start Audit
    </button>
  </div>
</form>

<script>
async function loadRooms() {
    const loc = document.getElementById('locationSelect').value;
    const roomSelect = document.getElementById('roomSelect');
    if (!loc) {
        roomSelect.innerHTML = '<option value="">Whole location (all rooms)</option>';
        return;
    }
    roomSelect.innerHTML = '<option value="">Loading rooms...</option>';
    try {
        const res = await fetch(`{{ route('admin.audit.rooms-for-location') }}?location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        roomSelect.innerHTML = '<option value="">Whole location (all rooms)</option>' +
            (data.rooms || []).map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');
    } catch (e) {
        roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
    }
}
</script>

@endsection
