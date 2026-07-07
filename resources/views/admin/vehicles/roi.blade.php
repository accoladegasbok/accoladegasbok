{{-- FILE: resources/views/admin/vehicles/roi.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'ROI Dashboard — ' . $vehicle->year . ' ' . $vehicle->make . ' ' . $vehicle->model)
@section('page-title', 'Vehicle ROI Dashboard')
@section('page-sub', $vehicle->year . ' ' . $vehicle->make . ' ' . $vehicle->model . ' — ' . $vehicle->vin)

@section('header-actions')
<a href="{{ route('admin.harvest.index') }}" class="border border-gray-200 text-gray-500 font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    ← Harvest History
</a>
@endsection

@section('content')

{{-- ── TOP METRIC CARDS ─────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

    {{-- Total Cost --}}
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 font-body uppercase tracking-wider mb-1">Total Cost</div>
        <div class="font-display font-700 text-navy text-2xl">
            {{ $sym }}{{ number_format($totalCost) }}
        </div>
        <div class="text-[10px] text-gray-400 mt-1">
            @if($vehicle->salvage_cost > 0) Salvage: {{ $sym }}{{ number_format($vehicle->salvage_cost) }} @endif
        </div>
    </div>

    {{-- Recovered So Far --}}
    <div class="stat-card text-center" style="border-left: 4px solid {{ $recoveryPct >= 100 ? '#1b9e5c' : ($recoveryPct >= 50 ? '#C8960C' : '#e74c3c') }}">
        <div class="text-xs text-gray-400 font-body uppercase tracking-wider mb-1">Recovered</div>
        <div class="font-display font-700 text-2xl" style="color: {{ $recoveryPct >= 100 ? '#1b9e5c' : ($recoveryPct >= 50 ? '#C8960C' : '#e74c3c') }}">
            {{ $sym }}{{ number_format($actualTotal) }}
        </div>
        <div class="text-[10px] text-gray-400 mt-1">{{ $recoveryPct }}% of total cost</div>
    </div>

    {{-- Still Needed --}}
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 font-body uppercase tracking-wider mb-1">Still Needed</div>
        <div class="font-display font-700 text-navy text-2xl">
            @if($remaining > 0)
                {{ $sym }}{{ number_format($remaining) }}
            @else
                <span class="text-green-600">✓ Done</span>
            @endif
        </div>
        <div class="text-[10px] text-gray-400 mt-1">to break even</div>
    </div>

    {{-- Days Tracking --}}
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 font-body uppercase tracking-wider mb-1">Days</div>
        <div class="font-display font-700 text-navy text-2xl">
            {{ $daysSinceAcquired ?? '—' }}
        </div>
        <div class="text-[10px] mt-1">
            @if($vehicle->break_even_reached_at)
                <span class="text-green-600 font-600">Break-even reached ✓</span>
            @elseif($daysRemaining !== null)
                <span class="{{ $daysRemaining <= 0 ? 'text-red-500' : 'text-gray-400' }}">
                    Target: {{ $breakEvenDays }} days
                    @if($daysRemaining > 0)({{ $daysRemaining }} left)
                    @else(overdue by {{ abs($daysRemaining) }}d)@endif
                </span>
            @else
                <span class="text-gray-400">Target: {{ $breakEvenDays }} days</span>
            @endif
        </div>
    </div>

</div>

{{-- ── RECOVERY PROGRESS BAR ───────────────────────────────────── --}}
<div class="stat-card mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-body font-500 text-gray-500 uppercase tracking-wider">Recovery Progress</span>
        <span class="font-display font-700 text-sm {{ $recoveryPct >= 100 ? 'text-green-600' : 'text-navy' }}">
            {{ $recoveryPct }}%
            @if($vehicle->break_even_reached_at)
                · Break-even reached {{ \Carbon\Carbon::parse($vehicle->break_even_reached_at)->format('d M Y') }}
            @endif
        </span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden">
        <div class="h-4 rounded-full transition-all duration-500"
             style="width: {{ min(100, $recoveryPct) }}%;
                    background: {{ $recoveryPct >= 100 ? '#1b9e5c' : ($recoveryPct >= 60 ? '#C8960C' : ($recoveryPct >= 30 ? '#f39c12' : '#e74c3c')) }}">
        </div>
    </div>
    <div class="flex justify-between mt-1.5 text-[10px] text-gray-400">
        <span>{{ $sym }}0</span>
        @if($projTotal > 0 && $projTotal != $totalCost)
        <span class="text-gold">Projected: {{ $sym }}{{ number_format($projTotal) }}</span>
        @endif
        <span>Target: {{ $sym }}{{ number_format($totalCost) }}</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- ── REVENUE BY CATEGORY ────────────────────────────────── --}}
    <div class="stat-card">
        <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Revenue by Category</h3>
        @if($revenueByCategory->isEmpty())
            <p class="text-xs text-gray-400 font-body text-center py-6">No parts sold yet from this vehicle.</p>
        @else
            <div class="space-y-3">
                @php $maxCatRevenue = $revenueByCategory->max('total'); @endphp
                @foreach($revenueByCategory as $cat)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-body text-gray-700">{{ $cat->part_category ?: 'Uncategorised' }}</span>
                        <div class="text-right">
                            <span class="font-display font-700 text-navy text-sm">{{ $sym }}{{ number_format($cat->total) }}</span>
                            <span class="text-[10px] text-gray-400 ml-1">{{ $cat->sales_count }} sold</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="h-2 rounded-full bg-gold" style="width: {{ $maxCatRevenue > 0 ? round(($cat->total / $maxCatRevenue) * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── VEHICLE COST BREAKDOWN ─────────────────────────────── --}}
    <div class="stat-card">
        <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Cost Breakdown</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                <tr class="py-2">
                    <td class="py-2 text-xs text-gray-500 font-body">Salvage / Purchase</td>
                    <td class="py-2 text-right font-display font-700 text-navy">{{ $sym }}{{ number_format($vehicle->salvage_cost ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-xs text-gray-500 font-body">Towing / Transport</td>
                    <td class="py-2 text-right font-display font-700 text-navy">{{ $sym }}{{ number_format($vehicle->towing_cost ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-xs text-gray-500 font-body">Processing / Labour</td>
                    <td class="py-2 text-right font-display font-700 text-navy">{{ $sym }}{{ number_format($vehicle->processing_cost ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-xs text-gray-500 font-body">Other Costs</td>
                    <td class="py-2 text-right font-display font-700 text-navy">{{ $sym }}{{ number_format($vehicle->other_cost ?? 0) }}</td>
                </tr>
                <tr class="bg-navy/5">
                    <td class="py-2.5 px-2 text-xs font-700 text-navy uppercase tracking-wide">Total Cost</td>
                    <td class="py-2.5 px-2 text-right font-display font-700 text-navy text-base">{{ $sym }}{{ number_format($totalCost) }}</td>
                </tr>
                <tr class="{{ $actualTotal >= $totalCost ? 'bg-green-50' : 'bg-yellow-50' }}">
                    <td class="py-2.5 px-2 text-xs font-700 uppercase tracking-wide {{ $actualTotal >= $totalCost ? 'text-green-700' : 'text-yellow-700' }}">Revenue Recovered</td>
                    <td class="py-2.5 px-2 text-right font-display font-700 text-base {{ $actualTotal >= $totalCost ? 'text-green-700' : 'text-yellow-700' }}">{{ $sym }}{{ number_format($actualTotal) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-3 gap-3 text-center">
            <div>
                <div class="font-display font-700 text-navy text-lg">{{ $totalParts }}</div>
                <div class="text-[10px] text-gray-400 font-body">Parts Harvested</div>
            </div>
            <div>
                <div class="font-display font-700 text-green-600 text-lg">{{ $soldParts }}</div>
                <div class="text-[10px] text-gray-400 font-body">Parts Sold</div>
            </div>
            <div>
                <div class="font-display font-700 text-gold text-lg">{{ $availableParts }}</div>
                <div class="text-[10px] text-gray-400 font-body">Still Available</div>
            </div>
        </div>
    </div>

</div>

{{-- ── RECENT SALES ────────────────────────────────────────────── --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Recent Sales from This Vehicle</h3>
    @if($recentSales->isEmpty())
        <p class="text-xs text-gray-400 font-body text-center py-6">No parts sold from this vehicle yet.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-navy text-white">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-display uppercase tracking-wide">Part</th>
                    <th class="px-3 py-2 text-left text-xs font-display uppercase tracking-wide">Category</th>
                    <th class="px-3 py-2 text-left text-xs font-display uppercase tracking-wide">Code</th>
                    <th class="px-3 py-2 text-right text-xs font-display uppercase tracking-wide">Revenue</th>
                    <th class="px-3 py-2 text-left text-xs font-display uppercase tracking-wide">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentSales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2.5 font-body font-500 text-gray-800 text-xs">{{ $sale->part_name }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-500">{{ $sale->part_category ?: '—' }}</td>
                    <td class="px-3 py-2.5 font-mono text-xs text-gray-500">{{ $sale->part_code ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-right font-display font-700 text-navy">{{ $sym }}{{ number_format($sale->revenue_amount) }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-400">{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── ALL HARVESTED PARTS ─────────────────────────────────────── --}}
<div class="stat-card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase">All Harvested Parts</h3>
        <a href="{{ route('admin.inventory.index', ['q' => $vehicle->vin]) }}"
           class="text-xs font-body text-gold hover:underline">View in Inventory →</a>
    </div>
    @if($parts->isEmpty())
        <p class="text-xs text-gray-400 font-body text-center py-6">No parts harvested yet.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-display text-gray-500 uppercase tracking-wide">Part</th>
                    <th class="px-3 py-2 text-left text-xs font-display text-gray-500 uppercase tracking-wide">Category</th>
                    <th class="px-3 py-2 text-left text-xs font-display text-gray-500 uppercase tracking-wide">Grade</th>
                    <th class="px-3 py-2 text-right text-xs font-display text-gray-500 uppercase tracking-wide">Retail</th>
                    <th class="px-3 py-2 text-right text-xs font-display text-gray-500 uppercase tracking-wide">Trade</th>
                    <th class="px-3 py-2 text-left text-xs font-display text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-display text-gray-500 uppercase tracking-wide">Bin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($parts as $p)
                <tr class="hover:bg-gray-50 {{ in_array($p->status, ['Sold','sold']) ? 'opacity-50' : '' }}">
                    <td class="px-3 py-2.5 font-body font-500 text-xs text-gray-800">{{ $p->part_name }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-500">{{ $p->part_category }}</td>
                    <td class="px-3 py-2.5">
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-700
                            {{ $p->condition_grade === 'A' ? 'bg-green-100 text-green-700' : ($p->condition_grade === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ $p->condition_grade }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-right font-700 text-navy text-xs">{{ $sym }}{{ number_format($p->price_local) }}</td>
                    <td class="px-3 py-2.5 text-right text-xs text-gold">
                        {{ $p->price_wholesale ? $sym . number_format($p->price_wholesale) : '—' }}
                    </td>
                    <td class="px-3 py-2.5">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-700
                            {{ in_array($p->status, ['Sold','sold']) ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700' }}">
                            {{ $p->status }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-[10px] text-gray-400 font-mono">{{ $p->bin_location ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
