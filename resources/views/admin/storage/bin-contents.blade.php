{{-- FILE: resources/views/admin/storage/bin-contents.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $shelf->full_bin_code . ' — Contents')
@section('page-title', $shelf->full_bin_code)
@section('page-sub', ($shelf->room_name ? $shelf->room_name . ' · ' : '') . $shelf->location . ' · ' . $items->count() . ' item(s) in this bin')

@section('header-actions')
<div class="flex gap-2">
  <a href="{{ route('admin.storage.show', $shelf->storage_room_id) }}" class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    ← All Bins in {{ $shelf->room_name }}
  </a>
  <a href="{{ route('admin.storage.shelves.barcode', $shelf->id) }}" target="_blank" class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-opacity-90 transition-colors">
    🏷 Print Bin Barcode
  </a>
</div>
@endsection

@section('content')

<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl px-4 py-3 mb-5 text-sm font-body">
  This is what scanning this bin's barcode shows — every part currently sitting in <strong>{{ $shelf->full_bin_code }}</strong>.
</div>

<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Code</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Brand / Model</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Grade</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Qty</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $item)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
          <td class="px-4 py-3 text-navy font-500">{{ $item->part_name }}</td>
          <td class="px-4 py-3 font-mono text-gray-500 text-xs">{{ $item->part_code }}</td>
          <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->part_category }}</td>
          <td class="px-4 py-3 text-gray-600 text-xs">{{ trim(($item->brand ?? '') . ' ' . ($item->model ?? '')) ?: '—' }}</td>
          <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->condition_grade ?? '—' }}</td>
          <td class="px-4 py-3 text-gray-600">{{ $item->stock_qty }}</td>
          <td class="px-4 py-3">
            <span class="badge {{ $item->status === 'Available' ? 'badge-green' : 'badge-gray' }}">{{ $item->status }}</span>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">This bin is currently empty.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
