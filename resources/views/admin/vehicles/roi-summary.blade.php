{{-- FILE: resources/views/admin/vehicles/roi-summary.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Vehicle ROI Summary')
@section('page-title', 'Vehicle ROI Summary')
@section('page-sub', 'Break-even tracking across all donor vehicles — sorted by recovery percentage')

@section('content')

{{-- ── LEGEND ───────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-2 mb-5 text-xs font-body">
    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-600">✓ Recovered — break-even reached</span>
    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-600">↑ On Track — >75% recovered</span>
    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-600">~ Progressing — 40–75%</span>
    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-600">! Early — <40% recovered</span>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-navy text-white">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Vehicle</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Location</th>
                <th class="px-4 py-3 text-right text-xs font-display uppercase tracking-wide">Total Cost</th>
                <th class="px-4 py-3 text-right text-xs font-display uppercase tracking-wide">Recovered</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide w-40">Progress</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Parts</th>
                <th class="px-4 py-3 text-xs font-display uppercase tracking-wide">ROI</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($vehicles as $v)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="font-body font-600 text-sm text-navy">{{ $v->year }} {{ $v->make }} {{ $v->model }}</div>
                    <div class="font-mono text-[10px] text-gray-400 mt-0.5">{{ $v->vin }}</div>
                    @if($v->primary_damage_code)
                    <span class="text-[9px] px-1.5 py-0.5 bg-red-50 text-red-600 rounded font-600">{{ $v->primary_damage_code }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-600">{{ $v->location ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-display font-700 text-navy text-sm">
                    @if($v->total_cost > 0)
                        {{ $v->sym }}{{ number_format($v->total_cost) }}
                    @else
                        <span class="text-gray-300 text-xs">Not set</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-display font-700 text-sm"
                    style="color: {{ $v->recovery_pct >= 100 ? '#1b9e5c' : ($v->recovery_pct >= 50 ? '#C8960C' : '#e74c3c') }}">
                    {{ $v->sym }}{{ number_format($v->actual_total) }}
                </td>
                <td class="px-4 py-3">
                    @if($v->total_cost > 0)
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full" style="width:{{ min(100,$v->recovery_pct) }}%;
                                background:{{ $v->recovery_pct>=100?'#1b9e5c':($v->recovery_pct>=60?'#C8960C':($v->recovery_pct>=30?'#f39c12':'#e74c3c')) }}"></div>
                        </div>
                        <span class="text-[10px] font-700 text-gray-600 w-8 text-right">{{ $v->recovery_pct }}%</span>
                    </div>
                    @else
                        <span class="text-[10px] text-gray-300">No cost data</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @switch($v->roi_status)
                        @case('recovered')
                            <span class="text-[10px] px-2 py-1 rounded-full bg-green-100 text-green-700 font-700">✓ Recovered</span>
                            @break
                        @case('on_track')
                            <span class="text-[10px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-700">↑ On Track</span>
                            @break
                        @case('progressing')
                            <span class="text-[10px] px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-700">~ Progressing</span>
                            @break
                        default:
                            <span class="text-[10px] px-2 py-1 rounded-full bg-red-100 text-red-700 font-700">! Early</span>
                    @endswitch
                </td>
                <td class="px-4 py-3 text-center text-xs text-gray-600">
                    {{ $v->parts_harvested ?? 0 }}
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.vehicles.roi', $v->id) }}"
                       class="text-xs font-body text-gold hover:underline font-600">
                        View →
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm font-body">
                    No donor vehicles with ROI data yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $vehicles->links() }}
    </div>
</div>
@endsection
