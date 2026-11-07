{{-- FILE: resources/views/admin/consumables/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Consumables')
@section('page-title', 'Consumables & Generic Parts')
@section('page-sub', 'Bulk stock items not tied to a donor vehicle')

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
  <a href="{{ route('admin.inventory.consumable.importbatch') }}"
     class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
    + Add Existing Product (new batch)
  </a>
  <a href="{{ route('admin.inventory.consumable.create') }}"
     class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-navy-light transition-colors">
    + Create New Product
  </a>

  <form method="GET" action="{{ route('admin.inventory.consumable.index') }}"
        class="flex gap-2 flex-1 min-w-[280px]">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search up to 10 items, separated by commas"
           class="flex-1 border border-gray-200 rounded-xl px-3.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
    <select name="location" class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
      @foreach($locations as $loc)
      <option value="{{ $loc }}" {{ $location === $loc ? 'selected' : '' }}>{{ $loc === 'all' ? 'All Locations' : $loc }}</option>
      @endforeach
    </select>
    <button type="submit" class="bg-navy text-white text-xs font-700 px-4 py-2 rounded-xl hover:bg-navy-light transition-colors">Search</button>
    @if($q || $location !== 'all')
    <a href="{{ route('admin.inventory.consumable.index') }}" class="border border-gray-200 text-gray-500 text-xs px-3 py-2 rounded-xl hover:bg-gray-50">Clear</a>
    @endif
  </form>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────── --}}
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
  <table class="w-full">
    <thead class="bg-navy text-white">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Part Name</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Brand</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Location</th>
        <th class="px-4 py-3 text-right text-xs font-display uppercase tracking-wide">Price</th>
        <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">Stock here</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">All locations</th>
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
          @if(!empty($batches[$item->id]))
          <details class="mt-1"><summary class="text-[10px] text-blue-600 cursor-pointer">Batches ({{ count($batches[$item->id]) }})</summary>
            @foreach($batches[$item->id] as $b)
            <div class="text-[10px] text-gray-500">{{ $b->batch_no }} · {{ $b->qty > 0 ? '+' : '' }}{{ $b->qty }} → {{ $b->balance_after }} · {{ $b->reason }}@if($b->supplier_ref) · {{ $b->supplier_ref }}@endif · {{ $b->staff_name }} · {{ \Illuminate\Support\Carbon::parse($b->created_at)->format('d M') }}</div>
            @endforeach
          </details>
          @endif
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->brand ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ $item->location }}</td>
        <td class="px-4 py-3 text-right font-700 text-gold text-sm">{{ $sym }}{{ number_format($item->price_local) }}</td>
        <td class="px-4 py-3 text-center">
          <span class="font-700 text-sm {{ $lowStock ? 'text-red-500' : 'text-navy' }}">{{ $item->stock_qty }}</span>
          @if($lowStock)<div class="text-[9px] text-red-400 font-600">LOW</div>@endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">
          @php $ev = $everywhere[\App\Http\Controllers\Admin\ConsumableController::itemKey($item->part_name, $item->brand, $item->part_category)] ?? null; @endphp
          @if($ev)
            <div class="font-700 text-navy">{{ $ev['total'] }} in total</div>
            @foreach($ev['locations'] as $locName => $locQty)
              <div class="text-[10px] text-gray-500">{{ $locName }}: <span class="font-700">{{ $locQty }}</span></div>
            @endforeach
          @else
            <span class="text-gray-300">&mdash;</span>
          @endif
        </td>
        <td class="px-4 py-3">
          <span class="text-[10px] px-2 py-0.5 rounded-full font-700
            {{ $item->status === 'Available' ? 'bg-green-100 text-green-700' : ($item->status === 'Under Review' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500') }}">
            {{ $item->status }}
          </span>
        </td>
        <td class="px-4 py-3 text-center">
          <div class="flex items-center justify-center gap-2">
            <form method="POST" action="{{ route('admin.inventory.consumable.addstock', $item->id) }}" class="flex items-center gap-1">
              @csrf
              <input type="number" name="qty" min="1" value="1" class="w-14 border border-gray-200 rounded-lg px-2 py-1 text-xs" title="Units to add">
              <input type="text" name="supplier_ref" maxlength="80" placeholder="Supplier lot" class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs" title="Optional supplier invoice / lot reference for this batch">
              <button class="text-xs border border-green-300 text-green-700 rounded-lg px-2 py-1 hover:bg-green-50 transition-colors" title="Adds units to this same item - no new number">+ Add</button>
            </form>
            <details class="relative text-left">
              <summary class="text-xs border border-blue-200 text-blue-700 rounded-lg px-2 py-1 cursor-pointer hover:bg-blue-50 list-none" title="Stock this same product at another location - no re-entry, same photos and number">Other location</summary>
              <form method="POST" action="{{ route('admin.inventory.consumable.stockat', $item->id) }}" class="absolute right-0 z-20 mt-1 w-60 bg-white border border-gray-200 rounded-xl shadow-lg p-3 space-y-2">
                @csrf
                <div class="text-[10px] text-gray-500">Same product, same number {{ $item->part_code }}. Photos are reused.</div>
                <select name="location" required class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs bg-white">
                  <option value="">Location...</option>
                  @foreach($stockLocations as $sl)<option value="{{ $sl }}">{{ $sl }}</option>@endforeach
                </select>
                <input type="number" name="qty" min="1" required placeholder="Quantity" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs">
                <input type="number" step="0.01" min="0" name="price_local" required value="{{ (float) $item->price_local }}" placeholder="Price there" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs">
                <input type="text" name="supplier_ref" maxlength="80" placeholder="Supplier lot (optional)" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs">
                <button class="w-full bg-navy text-white text-xs rounded-lg py-1.5">Stock here</button>
              </form>
            </details>
            <a href="{{ route('admin.inventory.consumable.edit', $item->id) }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1 hover:border-navy hover:text-navy transition-colors">
              Edit
            </a>
            <form method="POST" action="{{ route('admin.inventory.consumable.destroy', $item->id) }}"
                  onsubmit="return confirm('Remove this consumable?')">
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
          No consumables found.
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
