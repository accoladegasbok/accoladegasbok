{{-- FILE: resources/views/admin/reports/vehicles.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Vehicles Report')
@section('page-title', 'Donor Vehicles Report')
@section('page-sub', 'Acquisition costs, ROI recovery and break-even status across all donor vehicles')

@section('content')

<form method="GET" action="{{ route('admin.reports.vehicles') }}"
      class="bg-white border border-gray-200 rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-end shadow-sm">
    <div>
        <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Location</label>
        <select name="location" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-yellow-400">
            @foreach($locations as $loc)
            <option value="{{ $loc }}" {{ $location===$loc?'selected':'' }}>{{ $loc==='all'?'All Locations':$loc }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-navy text-white font-700 text-sm px-5 py-2 rounded-xl">Apply</button>
</form>

{{-- KPIs --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Vehicles</div>
        <div class="font-display font-700 text-navy text-2xl">{{ $totalVehicles }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Break-Even Reached</div>
        <div class="font-display font-700 text-green-600 text-2xl">{{ $recovered }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Cost</div>
        <div class="font-display font-700 text-gold text-xl">{{ number_format($totalCostAll) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Recovered</div>
        <div class="font-display font-700 text-2xl {{ $totalRecovered >= $totalCostAll ? 'text-green-600' : 'text-navy' }}">
            {{ number_format($totalRecovered) }}
        </div>
    </div>
</div>

{{-- Vehicles table --}}
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <table class="w-full">
        <thead class="bg-navy text-white">
            <tr>
                <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Vehicle</th>
                <th class="px-4 py-3 text-right text-xs uppercase tracking-wide">Cost</th>
                <th class="px-4 py-3 text-right text-xs uppercase tracking-wide">Recovered</th>
                <th class="px-4 py-3 text-left text-xs uppercase tracking-wide w-36">Progress</th>
                <th class="px-4 py-3 text-center text-xs uppercase tracking-wide">Parts</th>
                <th class="px-4 py-3 text-center text-xs uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 text-xs uppercase tracking-wide">ROI</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($vehicles as $v)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="font-600 text-sm text-navy">{{ $v->year }} {{ $v->make }} {{ $v->model }}</div>
                    <div class="text-[10px] text-gray-400 font-mono">{{ $v->vin }}</div>
                    @if($v->primary_damage_code)
                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-700">{{ $v->primary_damage_code }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-700 text-gold text-sm">{{ $v->sym }}{{ number_format($v->total_cost) }}</td>
                <td class="px-4 py-3 text-right font-700 text-sm"
                    style="color:{{ $v->recovery_pct>=100?'#16a34a':($v->recovery_pct>=50?'#c9a84c':'#ef4444') }}">
                    {{ $v->sym }}{{ number_format($v->actual_total??0) }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full"
                                 style="width:{{ min(100,$v->recovery_pct) }}%;background:{{ $v->recovery_pct>=100?'#16a34a':($v->recovery_pct>=50?'#c9a84c':'#ef4444') }}"></div>
                        </div>
                        <span class="text-[10px] font-700 text-gray-500 w-8 text-right">{{ $v->recovery_pct }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center text-xs text-gray-600">{{ $v->parts_harvested ?? 0 }}</td>
                <td class="px-4 py-3 text-center">
                    @if($v->break_even_reached_at)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-700">✓ Done</span>
                    @elseif($v->recovery_pct >= 75)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-700">On Track</span>
                    @elseif($v->recovery_pct >= 40)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-700">Progressing</span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-700">Early</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.vehicles.roi', $v->id) }}" class="text-xs text-gold hover:underline">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">No vehicles with cost data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
