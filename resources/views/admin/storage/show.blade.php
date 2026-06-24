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
    <span class="text-xs text-gray-400 font-body">{{ $shelves->count() }} total</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
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
          <td class="px-4 py-3 font-mono font-700 text-navy">{{ $shelf->full_bin_code }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->shelf_code }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->column_number ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $shelf->space_number ?? '—' }}</td>
          <td class="px-4 py-3">
            @php $count = $partsCount[$shelf->id] ?? 0; @endphp
            <span class="badge {{ $count > 0 ? 'badge-blue' : 'badge-gray' }}">{{ $count }} part{{ $count !== 1 ? 's' : '' }}</span>
          </td>
          <td class="px-4 py-3 text-right">
            <form method="POST" action="{{ route('admin.storage.shelves.destroy', $shelf->id) }}"
                  onsubmit="return confirm('Delete bin {{ $shelf->full_bin_code }}?')" class="inline">
              @csrf @method('DELETE')
              <button type="submit" class="text-xs font-body border border-red-200 text-red-400 hover:border-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg transition-colors">
                Delete
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No bins yet — use the forms above to create some.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
