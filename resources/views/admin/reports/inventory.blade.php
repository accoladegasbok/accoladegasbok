{{-- FILE: resources/views/admin/reports/inventory.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Inventory Report')
@section('page-title', 'Inventory Report')
@section('page-sub', 'Stock levels, value, low stock alerts, major components and legal trace parts')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('admin.reports.inventory') }}"
      class="bg-white border border-gray-200 rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-end shadow-sm">
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Location</label>
        <select name="location" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-yellow-400">
            @foreach($locations as $loc)
            <option value="{{ $loc }}" {{ $location===$loc?'selected':'' }}>{{ $loc==='all'?'All Locations':$loc }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
        <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-yellow-400">
            <option value="all" {{ $status==='all'?'selected':'' }}>All Statuses</option>
            <option value="Available" {{ $status==='Available'?'selected':'' }}>Available</option>
            <option value="Sold" {{ $status==='Sold'?'selected':'' }}>Sold</option>
            <option value="Reserved" {{ $status==='Reserved'?'selected':'' }}>Reserved</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
        <select name="category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-yellow-400">
            <option value="all">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ $category===$cat?'selected':'' }}>{{ $cat }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-navy text-white font-700 text-sm px-5 py-2 rounded-xl hover:bg-navy-light transition-colors">Apply</button>
    <a href="{{ route('admin.reports.inventory') }}" class="border border-gray-200 text-gray-500 text-sm px-4 py-2 rounded-xl hover:bg-gray-50">Reset</a>
</form>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Parts</div>
        <div class="font-display font-700 text-navy text-2xl">{{ number_format($totalParts) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available</div>
        <div class="font-display font-700 text-green-600 text-2xl">{{ number_format($availableParts) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Sold</div>
        <div class="font-display font-700 text-gray-400 text-2xl">{{ number_format($soldParts) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Stock Value</div>
        <div class="font-display font-700 text-gold text-xl">{{ number_format($totalValue) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- By Category --}}
    <div class="stat-card">
        <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">By Category</h3>
        @php $maxCat = $byCategory->max('count'); @endphp
        <div class="space-y-2">
            @forelse($byCategory as $cat)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-700">{{ $cat->part_category ?: 'Uncategorised' }}</span>
                    <span class="font-700 text-navy">{{ number_format($cat->count) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-gold" style="width:{{ $maxCat>0?round(($cat->count/$maxCat)*100):0 }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">No data</p>
            @endforelse
        </div>
    </div>

    {{-- By Location --}}
    <div class="stat-card">
        <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">By Location</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @forelse($byLocation as $loc)
                <tr>
                    <td class="py-2 text-gray-700 text-xs">{{ $loc->location ?: 'No location' }}</td>
                    <td class="py-2 text-right font-700 text-navy">{{ number_format($loc->count) }}</td>
                    <td class="py-2 text-right text-gold text-xs">{{ number_format($loc->value) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-4 text-center text-gray-400 text-xs">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Low Stock --}}
@if($lowStock->isNotEmpty())
<div class="stat-card mb-6" style="border-left:4px solid #ef4444;">
    <h3 class="font-display font-700 text-red-600 text-sm uppercase tracking-wide mb-4">⚠ Low Stock (≤1 unit)</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-red-50"><tr>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Part</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Code</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Location</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Bin</th>
                <th class="px-3 py-2 text-right text-xs text-red-600 uppercase">Qty</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($lowStock as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-xs font-600 text-navy">{{ $p->part_name }}</td>
                    <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ $p->part_code }}</td>
                    <td class="px-3 py-2 text-xs text-gray-500">{{ $p->location }}</td>
                    <td class="px-3 py-2 text-xs text-gray-500">{{ $p->bin_location ?? '—' }}</td>
                    <td class="px-3 py-2 text-right font-700 text-red-500">{{ $p->stock_qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Major Components --}}
@if($majorComponents->isNotEmpty())
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">⚡ Major Components in Stock</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-yellow-50"><tr>
                <th class="px-3 py-2 text-left text-xs text-yellow-700 uppercase">Part</th>
                <th class="px-3 py-2 text-left text-xs text-yellow-700 uppercase">Code</th>
                <th class="px-3 py-2 text-left text-xs text-yellow-700 uppercase">Location</th>
                <th class="px-3 py-2 text-right text-xs text-yellow-700 uppercase">Price</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($majorComponents as $p)
                @php $sym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$p->currency_code??'NGN']??'₦'; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-xs font-600 text-navy">{{ $p->part_name }}</td>
                    <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ $p->part_code }}</td>
                    <td class="px-3 py-2 text-xs text-gray-500">{{ $p->location }}</td>
                    <td class="px-3 py-2 text-right font-700 text-gold text-xs">{{ $sym }}{{ number_format($p->price_local) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Legal Trace --}}
@if($legalTraceParts->isNotEmpty())
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">⚠ Legal Trace Parts in Stock</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-red-50"><tr>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Part</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Code</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Location</th>
                <th class="px-3 py-2 text-left text-xs text-red-600 uppercase">Doc on File</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($legalTraceParts as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-xs font-600 text-navy">{{ $p->part_name }}</td>
                    <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ $p->part_code }}</td>
                    <td class="px-3 py-2 text-xs text-gray-500">{{ $p->location }}</td>
                    <td class="px-3 py-2 text-xs {{ $p->legal_trace_doc ? 'text-green-600 font-600' : 'text-red-400' }}">
                        {{ $p->legal_trace_doc ?? '— Not recorded' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
