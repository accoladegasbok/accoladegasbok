{{-- FILE: resources/views/admin/reports/financial.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Financial Report')
@section('page-title', 'Financial Report')
@section('page-sub', 'Revenue, ROI, and sales performance — ' . \Carbon\Carbon::parse($from)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($to)->format('d M Y'))

@section('header-actions')
<a href="{{ route('admin.reports.financial.export', request()->query()) }}"
   class="border border-[#C8960C] text-[#C8960C] font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-[#C8960C]/10 transition">
    ⬇ Export CSV
</a>
@endsection

@section('content')

{{-- ── FILTERS ─────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.reports.financial') }}"
      class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-slate-400 mb-1 uppercase tracking-wide">From</label>
        <input type="date" name="from" value="{{ $from }}"
               class="bg-[#0f172a] border border-[#334155] rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C8960C]">
    </div>
    <div>
        <label class="block text-xs text-slate-400 mb-1 uppercase tracking-wide">To</label>
        <input type="date" name="to" value="{{ $to }}"
               class="bg-[#0f172a] border border-[#334155] rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C8960C]">
    </div>
    <div>
        <label class="block text-xs text-slate-400 mb-1 uppercase tracking-wide">Location</label>
        <select name="location" class="bg-[#0f172a] border border-[#334155] rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#C8960C]">
            @foreach($locations as $loc)
            <option value="{{ $loc }}" {{ $location === $loc ? 'selected' : '' }}>
                {{ $loc === 'all' ? 'All Locations' : $loc }}
            </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-[#C8960C] text-[#0f172a] font-700 text-sm px-5 py-2 rounded-xl hover:bg-yellow-400 transition">
        Apply Filter
    </button>
    {{-- Quick ranges --}}
    <div class="flex gap-2 ml-auto flex-wrap">
        @php
            $ranges = [
                'Today'     => [today(), today()],
                'This Week' => [now()->startOfWeek(), now()->endOfWeek()],
                'This Month'=> [now()->startOfMonth(), now()->endOfMonth()],
                'Last Month'=> [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
                'This Year' => [now()->startOfYear(), now()->endOfYear()],
            ];
        @endphp
        @foreach($ranges as $label => [$start, $end])
        <a href="{{ route('admin.reports.financial', array_merge(request()->query(), ['from' => $start->toDateString(), 'to' => $end->toDateString()])) }}"
           class="text-xs px-3 py-1.5 rounded-lg {{ $from === $start->toDateString() && $to === $end->toDateString() ? 'bg-[#C8960C] text-[#0f172a] font-700' : 'bg-[#334155] text-slate-300 hover:bg-[#475569]' }} transition">
            {{ $label }}
        </a>
        @endforeach
    </div>
</form>

{{-- ── KPI CARDS ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 text-center">
        <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Total Revenue</div>
        <div class="font-display font-700 text-[#C8960C] text-2xl">
            ₦{{ number_format($totalRevenue) }}
        </div>
        <div class="text-[10px] text-slate-500 mt-1">{{ $totalSales }} parts sold</div>
    </div>
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 text-center">
        <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Avg Sale Value</div>
        <div class="font-display font-700 text-white text-2xl">
            ₦{{ number_format($avgSaleValue) }}
        </div>
        <div class="text-[10px] text-slate-500 mt-1">per part sold</div>
    </div>
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 text-center">
        <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Vehicles Recovered</div>
        <div class="font-display font-700 text-green-400 text-2xl">{{ $vehiclesRecovered }}</div>
        <div class="text-[10px] text-slate-500 mt-1">break-even reached</div>
    </div>
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 text-center">
        <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Still Recovering</div>
        <div class="font-display font-700 text-yellow-400 text-2xl">{{ $vehiclesPending }}</div>
        <div class="text-[10px] text-slate-500 mt-1">vehicles in progress</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- ── REVENUE BY CATEGORY ────────────────────────────────── --}}
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-5">
        <h3 class="text-white font-700 text-sm uppercase tracking-wide mb-4">Revenue by Part Category</h3>
        @if($revenueByCategory->isEmpty())
            <p class="text-slate-500 text-xs text-center py-8">No sales data for this period.</p>
        @else
        @php $maxCat = $revenueByCategory->max('total'); @endphp
        <div class="space-y-3">
            @foreach($revenueByCategory as $cat)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-slate-300 text-xs">{{ $cat->part_category ?: 'Uncategorised' }}</span>
                    <div class="text-right">
                        <span class="text-[#C8960C] font-700 text-sm">₦{{ number_format($cat->total) }}</span>
                        <span class="text-slate-500 text-[10px] ml-1">{{ $cat->sales_count }} sold</span>
                    </div>
                </div>
                <div class="w-full bg-[#0f172a] rounded-full h-2">
                    <div class="h-2 rounded-full bg-[#C8960C]"
                         style="width:{{ $maxCat > 0 ? round(($cat->total / $maxCat) * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── TOP 10 PARTS ────────────────────────────────────────── --}}
    <div class="bg-[#1e293b] border border-[#334155] rounded-xl p-5">
        <h3 class="text-white font-700 text-sm uppercase tracking-wide mb-4">Top 10 Best Selling Parts</h3>
        @if($topParts->isEmpty())
            <p class="text-slate-500 text-xs text-center py-8">No sales data for this period.</p>
        @else
        <div class="space-y-2">
            @foreach($topParts as $i => $part)
            <div class="flex items-center justify-between py-1.5 border-b border-[#334155]/50">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#334155] flex items-center justify-center text-[10px] text-slate-400 font-700">{{ $i + 1 }}</span>
                    <span class="text-slate-300 text-xs">{{ $part->part_name }}</span>
                </div>
                <div class="text-right">
                    <div class="text-[#C8960C] font-700 text-xs">₦{{ number_format($part->total_revenue) }}</div>
                    <div class="text-slate-500 text-[10px]">{{ $part->times_sold }} sold</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

{{-- ── DAILY REVENUE TREND ─────────────────────────────────────── --}}
@if($dailyRevenue->isNotEmpty())
<div class="bg-[#1e293b] border border-[#334155] rounded-xl p-5 mb-6">
    <h3 class="text-white font-700 text-sm uppercase tracking-wide mb-4">Daily Revenue Trend</h3>
    @php
        $maxDay = $dailyRevenue->max();
        $days   = $dailyRevenue->keys();
    @endphp
    <div class="flex items-end gap-1 h-24 overflow-x-auto pb-2">
        @foreach($dailyRevenue as $date => $amount)
        @php $pct = $maxDay > 0 ? ($amount / $maxDay) * 100 : 0; @endphp
        <div class="flex flex-col items-center flex-shrink-0" style="min-width: 28px;">
            <div class="w-5 rounded-t-sm bg-[#C8960C]/80 hover:bg-[#C8960C] transition"
                 style="height: {{ max(4, $pct * 0.88) }}px"
                 title="{{ $date }}: ₦{{ number_format($amount) }}">
            </div>
            <span class="text-[8px] text-slate-600 mt-1 rotate-0">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── WHOLESALE VS RETAIL MIX ─────────────────────────────────── --}}
@if($wholesaleRevenue && $wholesaleRevenue->wholesale_line_count > 0)
<div class="bg-[#1e293b] border border-[#334155] rounded-xl p-5 mb-6">
    <h3 class="text-white font-700 text-sm uppercase tracking-wide mb-4">Wholesale vs Retail Sales Mix</h3>
    <div class="grid grid-cols-3 gap-4 text-center">
        <div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Retail Total</div>
            <div class="text-[#C8960C] font-700 text-xl">₦{{ number_format($wholesaleRevenue->retail_total ?? 0) }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Wholesale Total</div>
            <div class="text-blue-400 font-700 text-xl">₦{{ number_format($wholesaleRevenue->wholesale_total ?? 0) }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">Trade Lines</div>
            <div class="text-white font-700 text-xl">{{ $wholesaleRevenue->wholesale_line_count ?? 0 }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── VEHICLE ROI TABLE ───────────────────────────────────────── --}}
<div class="bg-[#1e293b] border border-[#334155] rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-[#334155] flex items-center justify-between">
        <h3 class="text-white font-700 text-sm uppercase tracking-wide">Vehicle ROI Status</h3>
        <a href="{{ route('admin.vehicles.roi-summary') }}" class="text-xs text-[#C8960C] hover:underline">
            Full ROI Summary →
        </a>
    </div>
    @if($vehicleRoi->isEmpty())
        <p class="text-slate-500 text-xs text-center py-8">No vehicles with cost data yet.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f172a]">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Vehicle</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 uppercase tracking-wide">Cost</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 uppercase tracking-wide">Recovered</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide w-36">Progress</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Days</th>
                    <th class="px-4 py-2.5 text-xs text-slate-400 uppercase tracking-wide">ROI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#334155]">
                @foreach($vehicleRoi as $v)
                <tr class="hover:bg-[#0f172a]/50">
                    <td class="px-4 py-3">
                        <div class="text-white text-xs font-600">{{ $v->year }} {{ $v->make }} {{ $v->model }}</div>
                        <div class="text-slate-500 text-[10px] mt-0.5">Acquired {{ $v->date_acquired ? \Carbon\Carbon::parse($v->date_acquired)->format('d M Y') : '—' }}</div>
                    </td>
                    <td class="px-4 py-3 text-right text-[#C8960C] font-700 text-sm">{{ $v->sym }}{{ number_format($v->total_cost) }}</td>
                    <td class="px-4 py-3 text-right font-700 text-sm"
                        style="color: {{ $v->recovery_pct >= 100 ? '#4ade80' : ($v->recovery_pct >= 50 ? '#C8960C' : '#f87171') }}">
                        {{ $v->sym }}{{ number_format($v->actual_total ?? 0) }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-[#334155] rounded-full h-1.5">
                                <div class="h-1.5 rounded-full"
                                     style="width:{{ min(100, $v->recovery_pct) }}%;
                                            background:{{ $v->recovery_pct>=100?'#4ade80':($v->recovery_pct>=50?'#C8960C':'#f87171') }}"></div>
                            </div>
                            <span class="text-[10px] text-slate-400 w-8 text-right">{{ $v->recovery_pct }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400">
                        {{ $v->days_acquired ?? '—' }}d
                        @if($v->break_even_days)
                            / {{ $v->break_even_days }}d target
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($v->break_even_reached_at)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-500/20 text-green-400 font-700">✓ Done</span>
                        @elseif($v->recovery_pct >= 75)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 font-700">On Track</span>
                        @elseif($v->recovery_pct >= 40)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400 font-700">Progressing</span>
                        @else
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 font-700">Early</span>
                        @endif
                        <a href="{{ route('admin.vehicles.roi', $v->id) }}"
                           class="block text-[10px] text-[#C8960C] hover:underline mt-0.5">Details →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
