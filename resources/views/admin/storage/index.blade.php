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

{{-- ── Rooms grouped by location ────────────────────────────────── --}}
@forelse($rooms as $location => $locationRooms)
<div class="mb-6">
  <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">{{ $location }}</h3>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($locationRooms as $room)
    <a href="{{ route('admin.storage.show', $room->id) }}" class="stat-card hover:shadow-md transition-shadow block">
      <div class="flex items-start justify-between">
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
    @endforeach
  </div>
</div>
@empty
<div class="stat-card text-center py-12">
  <div class="text-gray-300 text-5xl mb-3">🏬</div>
  <div class="text-gray-500 font-body text-sm">No store rooms set up yet. Add your first one above.</div>
</div>
@endforelse

@endsection
