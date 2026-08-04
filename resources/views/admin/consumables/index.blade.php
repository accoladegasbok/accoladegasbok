{{-- FILE: resources/views/admin/consumables/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Consumables & Others')
@section('page-title', 'Consumables & Others')
@section('page-sub', 'Bulk stock items not tied to a donor vehicle — oils, filters, electronics, computers and more')

@section('content')

{{-- ── KPI CARDS ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-6">
  <div class="stat-card text-center">
    <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Items</div>
    <div class="font-display font-700 text-navy text-2xl">{{ number_format($totalItems) }}</div>
  </div>
  <div class="stat-card text-center">
    <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Stock Value</div>
    <div class="font-display font-700 text-gold text-2xl">{{ number_format($totalValue) }}</div>
  </div>
  <div class="stat-card text-center" style="{{ $lowStock > 0 ? 'border-left:4px solid #ef4444' : '' }}">
    <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Low Stock</div>
    <div class="font-display font-700 text-2xl {{ $lowStock > 0 ? 'text-red-500' : 'text-green-600' }}">{{ $lowStock }}</div>
    <div class="text-[10px] text-gray-400 mt-0.5">≤ 3 units</div>
  </div>
</div>

{{-- ── ACTIONS + FILTERS ───────────────────────────────────────── --}}
<div class="flex flex-wrap gap-3 mb-5 items-center">
  <a href="{{ route('admin.inventory.consumable.create') }}"
     class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
    + Add Item
  </a>

  <form method="GET" action="{{ route('admin.inventory.consumable.index') }}"
        class="flex gap-2 flex-1 min-w-[280px]">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search item name or code..."
           class="flex-1 border border-gray-200 rounded-xl px-3.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
    {{-- NEW: category filter — this page now covers all four grouped
         categories, not just Consumable. --}}
    <select name="category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
      <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
      @foreach($categories as $cat)
      <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
      @endforeach
    </select>
    <select name="location" class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
      @foreach($locations as $loc)
      <option value="{{ $loc }}" {{ $location === $loc ? 'selected' : '' }}>{{ $loc === 'all' ? 'All Locations' : $loc }}</option>
      @endforeach
    </select>
    <button type="submit" class="bg-navy text-white text-xs font-700 px-4 py-2 rounded-xl hover:bg-navy-light transition-colors">Search</button>
    @if($q || $location !== 'all' || $category !== 'all')
    <a href="{{ route('admin.inventory.consumable.index') }}" class="border border-gray-200 text-gray-500 text-xs px-3 py-2 rounded-xl hover:bg-gray-50">Clear</a>
    @endif
  </form>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────── --}}
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
  <table class="w-full">
    <thead class="bg-navy text-white">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Item Name</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Category</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Brand</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Location</th>
        <th class="px-4 py-3 text-right text-xs font-display uppercase tracking-wide">Price</th>
        <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">Stock</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Status</th>
        <th class="px-4 py-3 text-xs font-display uppercase tracking-wide">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($consumables as $item)
      @php
        $sym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$item->currency_code ?? 'NGN'] ?? '₦';
        $lowStock = $item->stock_qty <= 3 && $item->stock_qty > 0;
      @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3">
          <div class="font-body font-600 text-sm text-navy">{{ $item->part_name }}</div>
          <div class="font-mono text-[10px] text-gray-400">{{ $item->part_code }}</div>
        </td>
        <td class="px-4 py-3">
          <span class="text-[10px] px-2 py-0.5 rounded-full font-700 bg-gray-100 text-gray-600">{{ $item->part_category }}</span>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->brand ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ $item->location }}</td>
        <td class="px-4 py-3 text-right font-700 text-gold text-sm">{{ $sym }}{{ number_format($item->price_local) }}</td>
        <td class="px-4 py-3 text-center">
          <span class="font-700 text-sm {{ $lowStock ? 'text-red-500' : 'text-navy' }}">{{ $item->stock_qty }}</span>
          @if($lowStock)<div class="text-[9px] text-red-400 font-600">LOW</div>@endif
        </td>
        <td class="px-4 py-3">
          <span class="text-[10px] px-2 py-0.5 rounded-full font-700
            {{ $item->status === 'Available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $item->status }}
          </span>
        </td>
        <td class="px-4 py-3 text-center">
          <div class="flex items-center justify-center gap-2">
            <a href="{{ route('admin.inventory.consumable.edit', $item->id) }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1 hover:border-navy hover:text-navy transition-colors">
              Edit
            </a>
            <form method="POST" action="{{ route('admin.inventory.consumable.destroy', $item->id) }}"
                  onsubmit="return confirm('Remove this item?')">
              @csrf @method('DELETE')
              <button class="text-xs border border-red-200 text-red-400 rounded-lg px-3 py-1 hover:bg-red-50 transition-colors">
                Remove
              </button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">
          No items found.
          <a href="{{ route('admin.inventory.consumable.create') }}" class="text-gold underline ml-1">Add the first one</a>.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-4 py-3 border-t border-gray-100">
    {{ $consumables->links() }}
  </div>
</div>

@endsection
