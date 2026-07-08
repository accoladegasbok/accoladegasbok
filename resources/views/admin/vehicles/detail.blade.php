{{-- FILE: resources/views/admin/vehicles/detail.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $vehicle->year . ' ' . $vehicle->make . ' ' . $vehicle->model)
@section('page-title', $vehicle->year . ' ' . $vehicle->make . ' ' . $vehicle->model)
@section('page-sub', 'VIN: ' . $vehicle->vin . ' · ' . ($vehicle->location ?? 'No location') . ' · Acquired ' . ($vehicle->date_acquired ? \Carbon\Carbon::parse($vehicle->date_acquired)->format('d M Y') : '—'))

@section('header-actions')
<a href="{{ route('admin.vehicles.roi', $vehicle->id) }}"
   class="bg-[#C8960C] text-[#0f172a] font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition">
    📊 ROI Dashboard
</a>
<a href="{{ route('admin.harvest.index') }}"
   class="border border-[#334155] text-slate-400 font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-[#1e293b] transition ml-2">
    ← All Vehicles
</a>
@endsection

@section('content')

{{-- ── VEHICLE SUMMARY ──────────────────────────────────────────── --}}
<div class="bg-[#1e293b] border border-[#334155] rounded-xl p-5 mb-6">
    <div class="flex flex-wrap gap-6 items-start">

        {{-- Vehicle info --}}
        <div class="flex-1 min-w-[200px]">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-xl bg-[#0f172a] flex items-center justify-center text-2xl">🚗</div>
                <div>
                    <div class="text-white font-display font-700 text-xl">
                        {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                        @if($vehicle->trim) <span class="text-slate-400 text-sm font-400">{{ $vehicle->trim }}</span>@endif
                    </div>
                    <div class="text-slate-400 text-xs font-mono mt-0.5">{{ $vehicle->vin }}</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                <div><span class="text-slate-500 text-xs">Engine</span><br><span class="text-slate-300">{{ $vehicle->engine ?? '—' }}</span></div>
                <div><span class="text-slate-500 text-xs">Colour</span><br><span class="text-slate-300">{{ $vehicle->colour ?? '—' }}</span></div>
                <div><span class="text-slate-500 text-xs">Mileage</span><br><span class="text-slate-300">{{ $vehicle->mileage ? number_format($vehicle->mileage) . ' mi' : '—' }}</span></div>
                <div><span class="text-slate-500 text-xs">Condition</span><br><span class="text-slate-300">{{ $vehicle->condition ?? '—' }}</span></div>
                <div><span class="text-slate-500 text-xs">Source</span><br><span class="text-slate-300">{{ $vehicle->source ?? '—' }}</span></div>
                <div><span class="text-slate-500 text-xs">Purpose</span><br>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                        {{ ($vehicle->vehicle_status ?? 'Parts') === 'Parts' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400' }}">
                        {{ $vehicle->vehicle_status ?? 'Parts' }}
                    </span>
                </div>
                @if($vehicle->primary_damage_code)
                <div><span class="text-slate-500 text-xs">Damage</span><br>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 font-700">
                        {{ $vehicle->primary_damage_code }}
                        @if($vehicle->secondary_damage_code) · {{ $vehicle->secondary_damage_code }}@endif
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- Cost breakdown --}}
        <div class="min-w-[200px]">
            <div class="text-xs text-slate-500 uppercase tracking-wide mb-2">Acquisition Costs</div>
            @php
                $sym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$vehicle->currency_code ?? 'NGN'] ?? '₦';
            @endphp
            <table class="text-sm w-full">
                <tr><td class="text-slate-500 text-xs pr-4 py-0.5">Purchase</td><td class="text-right text-slate-300">{{ $sym }}{{ number_format($vehicle->salvage_cost ?? 0) }}</td></tr>
                <tr><td class="text-slate-500 text-xs pr-4 py-0.5">Towing</td><td class="text-right text-slate-300">{{ $sym }}{{ number_format($vehicle->towing_cost ?? 0) }}</td></tr>
                <tr><td class="text-slate-500 text-xs pr-4 py-0.5">Processing</td><td class="text-right text-slate-300">{{ $sym }}{{ number_format($vehicle->processing_cost ?? 0) }}</td></tr>
                <tr><td class="text-slate-500 text-xs pr-4 py-0.5">Other</td><td class="text-right text-slate-300">{{ $sym }}{{ number_format($vehicle->other_cost ?? 0) }}</td></tr>
                <tr class="border-t border-[#334155]">
                    <td class="text-[#C8960C] font-700 text-xs pr-4 pt-1">TOTAL</td>
                    <td class="text-right text-[#C8960C] font-700">{{ $sym }}{{ number_format($vehicle->total_cost ?? 0) }}</td>
                </tr>
            </table>
        </div>

        {{-- ROI snapshot --}}
        <div class="min-w-[180px]">
            <div class="text-xs text-slate-500 uppercase tracking-wide mb-2">ROI Snapshot</div>
            @php
                $totalCost   = (float) ($vehicle->total_cost ?? 0);
                $actualTotal = (float) ($projection->actual_total ?? 0);
                $pct         = $totalCost > 0 ? min(100, round(($actualTotal / $totalCost) * 100, 1)) : 0;
                $remaining   = max(0, $totalCost - $actualTotal);
            @endphp
            <div class="text-2xl font-display font-700 mb-1"
                 style="color: {{ $pct >= 100 ? '#4ade80' : ($pct >= 50 ? '#C8960C' : '#f87171') }}">
                {{ $pct }}%
            </div>
            <div class="w-full bg-[#0f172a] rounded-full h-2 mb-2">
                <div class="h-2 rounded-full"
                     style="width:{{ $pct }}%;
                            background:{{ $pct>=100?'#4ade80':($pct>=50?'#C8960C':'#f87171') }}"></div>
            </div>
            <div class="text-xs text-slate-400">
                Recovered: <span class="text-white font-600">{{ $sym }}{{ number_format($actualTotal) }}</span><br>
                Remaining: <span class="{{ $remaining > 0 ? 'text-red-400' : 'text-green-400' }} font-600">{{ $sym }}{{ number_format($remaining) }}</span><br>
                @if($projection && $projection->break_even_reached_at)
                    <span class="text-green-400 font-600">✓ Break-even reached {{ \Carbon\Carbon::parse($projection->break_even_reached_at)->format('d M Y') }}</span>
                @elseif($vehicle->break_even_days)
                    Target: {{ $vehicle->break_even_days }} days
                    @if($vehicle->date_acquired)
                        ({{ now()->diffInDays($vehicle->date_acquired) }} elapsed)
                    @endif
                @endif
            </div>
            <a href="{{ route('admin.vehicles.roi', $vehicle->id) }}"
               class="inline-block mt-2 text-xs text-[#C8960C] hover:underline">
                Full ROI Details →
            </a>
        </div>

    </div>
</div>

{{-- ── PARTS GRID ───────────────────────────────────────────────── --}}
<div class="bg-[#1e293b] border border-[#334155] rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-[#334155] flex items-center justify-between">
        <div>
            <h3 class="text-white font-700 text-sm uppercase tracking-wide">Harvested Parts</h3>
            <p class="text-slate-500 text-xs mt-0.5">
                {{ $parts->count() }} total ·
                <span class="text-green-400">{{ $parts->where('status','Available')->count() }} available</span> ·
                <span class="text-slate-500">{{ $parts->whereIn('status',['Sold','sold'])->count() }} sold</span>
            </p>
        </div>
        <div class="flex gap-2">
            {{-- Barcode batch print --}}
            <a href="{{ route('admin.inventory.barcode-label', ['ids' => $parts->pluck('id')->join(','), 'size' => 'large']) }}"
               target="_blank"
               class="text-xs border border-[#334155] text-slate-400 rounded-lg px-3 py-1.5 hover:bg-[#334155] transition">
                🏷 Print All Labels
            </a>
        </div>
    </div>

    @if($parts->isEmpty())
        <div class="px-5 py-12 text-center text-slate-500 text-sm">No parts harvested from this vehicle yet.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f172a]">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Part</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Code</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Grade</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 uppercase tracking-wide">Retail</th>
                    <th class="px-4 py-2.5 text-right text-xs text-slate-400 uppercase tracking-wide">Trade</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Bin</th>
                    <th class="px-4 py-2.5 text-left text-xs text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-2.5 text-xs text-slate-400 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#334155]">
                @foreach($parts as $part)
                @php
                    $partSym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$part->currency_code ?? 'NGN'] ?? '₦';
                    $isSold  = in_array($part->status, ['Sold','sold']);
                @endphp
                <tr class="hover:bg-[#0f172a]/50 {{ $isSold ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3">
                        <div class="text-white text-xs font-600">{{ $part->part_name }}</div>
                        <div class="text-slate-500 text-[10px] mt-0.5">{{ $part->part_category }}</div>
                        @if($part->conditions_and_options)
                        <div class="text-slate-600 text-[10px] italic">{{ $part->conditions_and_options }}</div>
                        @endif
                        @if($part->is_major_component)
                        <span class="text-[8px] px-1.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400 font-700">⚡ MAJOR</span>
                        @endif
                        @if($part->legal_trace_required)
                        <span class="text-[8px] px-1.5 py-0.5 rounded bg-red-500/20 text-red-400 font-700">⚠ LEGAL TRACE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-[10px] text-slate-400">{{ $part->part_code }}</td>
                    <td class="px-4 py-3">
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-700
                            {{ $part->condition_grade === 'A' ? 'bg-green-500/20 text-green-400' : ($part->condition_grade === 'B' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                            {{ $part->condition_grade }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-700 text-[#C8960C] text-sm">
                        {{ $partSym }}{{ number_format($part->price_local) }}
                    </td>
                    <td class="px-4 py-3 text-right text-slate-400 text-xs">
                        {{ $part->price_wholesale ? $partSym . number_format($part->price_wholesale) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-[10px] text-slate-400 font-mono">
                        {{ $part->bin_location ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                            {{ $isSold ? 'bg-slate-500/20 text-slate-400' : 'bg-green-500/20 text-green-400' }}">
                            {{ $part->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('admin.inventory.show', $part->id) }}"
                               class="text-[10px] text-slate-400 hover:text-white border border-[#334155] rounded px-2 py-0.5 hover:border-[#C8960C] transition">
                                View
                            </a>
                            <a href="{{ route('admin.inventory.barcode-label', ['ids' => $part->id, 'size' => 'large']) }}"
                               target="_blank"
                               class="text-[10px] text-slate-400 hover:text-white border border-[#334155] rounded px-2 py-0.5 hover:border-[#C8960C] transition">
                                🏷
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── HARVEST SESSIONS ─────────────────────────────────────────── --}}
<div class="bg-[#1e293b] border border-[#334155] rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-[#334155]">
        <h3 class="text-white font-700 text-sm uppercase tracking-wide">Harvest Sessions</h3>
    </div>
    @if($sessions->isEmpty())
        <div class="px-5 py-8 text-center text-slate-500 text-sm">No harvest sessions found.</div>
    @else
    <div class="divide-y divide-[#334155]">
        @foreach($sessions as $s)
        <div class="px-5 py-3 flex items-center justify-between gap-4">
            <div>
                <div class="text-slate-300 text-xs font-600">
                    Session #{{ $s->id }} — {{ $s->parts_harvested ?? 0 }} parts harvested
                </div>
                <div class="text-slate-500 text-[10px] mt-0.5">
                    {{ \Carbon\Carbon::parse($s->created_at)->format('d M Y H:i') }}
                    · By: {{ $s->staff_name ?? 'Unknown' }}
                    @if($s->completed_at) · Completed {{ \Carbon\Carbon::parse($s->completed_at)->format('d M Y') }}@endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                    {{ $s->status === 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">
                    {{ ucfirst($s->status) }}
                </span>
                @if($s->status !== 'completed')
                <a href="{{ route('admin.harvest.checklist', $s->id) }}"
                   class="text-[10px] text-[#C8960C] border border-[#C8960C]/30 rounded px-2 py-0.5 hover:bg-[#C8960C]/10 transition">
                    Continue →
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
