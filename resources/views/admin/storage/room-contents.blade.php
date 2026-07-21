{{-- FILE: resources/views/admin/storage/room-contents.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $room->name . ' — Contents')
@section('page-title', $room->name . ' — All Items')
@section('page-sub', $room->location . ' · Room code: ' . $room->code . ' · ' . $items->count() . ' item(s) across all bins')

@section('header-actions')
<div class="flex gap-2">
  <a href="{{ route('admin.storage.show', $room->id) }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    ← Manage Bins
  </a>
  <a href="{{ route('admin.storage.room-barcode', $room->id) }}" target="_blank" class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-opacity-90 transition-colors">
    🏷 Print Room Barcode
  </a>
</div>
@endsection

@section('content')

<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl px-4 py-3 mb-5 text-sm font-body">
  This is what scanning this room's barcode shows — every part currently stored across every bin in {{ $room->name }}, in one flattened list.
</div>

<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Bin</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Code</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Grade</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Qty</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3 font-mono font-700 text-navy text-xs">{{ $item->full_bin_code }}</td>
          <td class="px-4 py-3 text-navy">{{ $item->part_name }}</td>
          <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $item->part_code }}</td>
          <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->part_category }}</td>
          <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->condition_grade ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $item->stock_qty }}</td>
          <td class="px-4 py-3">
            <span class="badge {{ $item->status === 'Available' ? 'badge-green' : 'badge-gray' }}">{{ $item->status }}</span>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No parts currently assigned to any bin in this room.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
