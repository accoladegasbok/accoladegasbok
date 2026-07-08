{{-- FILE: resources/views/admin/compatibility/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Compatibility Checker')
@section('page-title', 'Compatibility Checker')
@section('page-sub', 'Powerlink-style interchange — find parts by vehicle, powered by interchange groups')

@section('content')

{{-- ── KPI CARDS ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Interchange Groups</div>
        <div class="font-display font-700 text-navy text-2xl">{{ number_format($totalGroups) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Vehicle Ranges</div>
        <div class="font-display font-700 text-navy text-2xl">{{ number_format($totalVehicles) }}</div>
    </div>
    <div class="stat-card text-center">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Manual (Confirmed)</div>
        <div class="font-display font-700 text-green-600 text-2xl">{{ number_format($manualGroups) }}</div>
    </div>
</div>

{{-- ── VEHICLE LOOKUP ───────────────────────────────────────────── --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">
        Check Compatible Parts for a Vehicle
    </h3>
    <p class="text-xs text-gray-400 font-body mb-4">
        Searches interchange groups first (most reliable), then direct year-range matches,
        then OEM-code heuristics — same priority order as Powerlink's EDEN lookup.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Make</label>
            <input type="text" id="chk_make" placeholder="e.g. TOYOTA"
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 uppercase">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Model</label>
            <input type="text" id="chk_model" placeholder="e.g. CAMRY"
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 uppercase">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Year</label>
            <input type="number" id="chk_year" placeholder="e.g. 2007" min="1980" max="{{ date('Y') + 1 }}"
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <button onclick="runCheck()"
                class="bg-gold text-navy font-display font-700 text-sm py-2.5 px-5 rounded-xl hover:bg-yellow-400 transition-colors">
            Find Compatible Parts
        </button>
    </div>

    <div id="checkLoading" class="mt-4 hidden text-sm text-gray-400">Searching interchange groups...</div>
    <div id="checkEmpty"   class="mt-4 hidden text-sm text-gray-400 text-center py-6">No compatible parts found in stock for that vehicle.</div>

    <div id="checkResults" class="mt-5 hidden">
        <div id="checkCount" class="text-sm font-600 text-navy mb-3"></div>

        {{-- Interchange + direct results --}}
        <div id="checkList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4"></div>

        {{-- Heuristic suggestions --}}
        <div id="suggestionsWrap" class="hidden mt-4">
            <div class="text-xs font-600 text-gray-500 uppercase tracking-wide mb-2">
                💡 Auto-detected suggestions (OEM code match — not yet confirmed)
            </div>
            <div id="suggestionsList" class="space-y-2"></div>
        </div>
    </div>
</div>

{{-- ── INTERCHANGE GROUPS TABLE ─────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Interchange Groups</h3>
    <form method="GET" action="{{ route('admin.compatibility.index') }}" class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search part or group code..."
               class="border border-gray-200 rounded-xl px-3.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400 w-56">
        <button type="submit" class="bg-navy text-white text-xs font-700 px-4 py-2 rounded-xl">Search</button>
        @if($q)<a href="{{ route('admin.compatibility.index') }}" class="border border-gray-200 text-gray-500 text-xs px-3 py-2 rounded-xl hover:bg-gray-50">Clear</a>@endif
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
    <table class="w-full">
        <thead class="bg-navy text-white">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Part Name</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Group Code</th>
                <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">Vehicles</th>
                <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">Available</th>
                <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">Total</th>
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Source</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($groups as $g)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="font-body font-600 text-sm text-navy">{{ $g->part_name }}</div>
                    <div class="text-[10px] text-gray-400">{{ $g->part_category }}</div>
                    @if($g->notes)<div class="text-[10px] text-gray-400 italic mt-0.5">{{ Str::limit($g->notes, 60) }}</div>@endif
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gold font-700">{{ $g->group_code }}</td>
                <td class="px-4 py-3 text-center text-sm text-gray-700 font-600">{{ $g->vehicle_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="font-700 text-sm {{ $g->parts_available > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $g->parts_available }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-500">{{ $g->parts_total }}</td>
                <td class="px-4 py-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                        {{ $g->source === 'manual' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $g->source === 'manual' ? '✓ Confirmed' : '~ Auto' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                    No interchange groups yet. Groups are created when you confirm interchange on the inventory edit page.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">{{ $groups->links() }}</div>
</div>

@endsection

@push('scripts')
<script>
async function runCheck() {
    const make  = document.getElementById('chk_make').value.trim().toUpperCase();
    const model = document.getElementById('chk_model').value.trim().toUpperCase();
    const year  = document.getElementById('chk_year').value.trim();

    if (!make || !model || !year) { alert('Please fill in make, model and year.'); return; }

    ['checkResults','checkEmpty','suggestionsWrap'].forEach(id => document.getElementById(id).classList.add('hidden'));
    document.getElementById('checkLoading').classList.remove('hidden');

    try {
        const res  = await fetch('{{ route('admin.compatibility.check') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ make, model, year }),
        });
        const data = await res.json();
        document.getElementById('checkLoading').classList.add('hidden');

        if (!data.count) {
            document.getElementById('checkEmpty').classList.remove('hidden');
        } else {
            document.getElementById('checkCount').textContent =
                `${data.count} compatible part(s) in stock for ${data.search}`;

            document.getElementById('checkList').innerHTML = data.results.map(p => `
                <div class="border border-gray-200 rounded-xl p-3 hover:border-yellow-400 transition-colors">
                    ${p.photo
                        ? `<img src="${p.photo}" class="w-full h-28 object-cover rounded-lg mb-2" onerror="this.src='/images/coming-soon.jpg'">`
                        : `<div class="w-full h-28 bg-gray-100 rounded-lg mb-2 flex items-center justify-center text-gray-300 text-xs">No photo</div>`}
                    <div class="font-600 text-sm text-navy">${p.part_name}</div>
                    <div class="font-mono text-[10px] text-gray-400">${p.part_code}</div>
                    ${p.fits_vehicles ? `<div class="text-[10px] text-blue-500 mt-1">Fits: ${p.fits_vehicles}</div>` : ''}
                    <div class="flex items-center justify-between mt-2">
                        <div>
                            <span class="font-700 text-gold text-sm">${p.price}</span>
                            ${p.price_wholesale ? `<span class="text-[10px] text-gray-400 ml-1">/ ${p.price_wholesale} trade</span>` : ''}
                        </div>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-700">Grade ${p.grade}</span>
                    </div>
                    ${p.combined_stock > 1 ? `<div class="text-[10px] text-blue-600 mt-1">Combined stock: ${p.combined_stock} units</div>` : ''}
                    <div class="text-[10px] text-gray-400 mt-1">${p.location}${p.bin ? ' · ' + p.bin : ''}</div>
                    <div class="flex gap-1 mt-2">
                        ${p.source === 'interchange' ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-700">✓ Interchange</span>` : ''}
                        ${p.source === 'direct' ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-700">Direct match</span>` : ''}
                        ${p.major_component ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-700">⚡ Major</span>` : ''}
                        ${p.legal_trace ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-700">⚠ Legal Trace</span>` : ''}
                    </div>
                    <a href="/admin/inventory/${p.id}" class="block text-center text-xs text-gold border border-gold/30 rounded-lg py-1.5 mt-2 hover:bg-gold/10 transition-colors">
                        View Part →
                    </a>
                </div>
            `).join('');

            document.getElementById('checkResults').classList.remove('hidden');

            // Heuristic suggestions
            if (data.suggestions && data.suggestions.length > 0) {
                document.getElementById('suggestionsList').innerHTML = data.suggestions.map(s => `
                    <div class="border border-yellow-200 bg-yellow-50 rounded-xl px-4 py-3">
                        <div class="text-xs font-700 text-yellow-800">${s.part_name} (${s.engine_code})</div>
                        <div class="text-[10px] text-yellow-600 mt-0.5">Also likely fits: ${s.vehicles.join(', ')}</div>
                        <div class="text-[10px] text-gray-500 mt-1">Auto-detected via OEM code — confirm on the inventory edit page to save as an interchange group</div>
                    </div>
                `).join('');
                document.getElementById('suggestionsWrap').classList.remove('hidden');
            }
        }
    } catch (e) {
        document.getElementById('checkLoading').classList.add('hidden');
        alert('Check failed. Please try again.');
    }
}

document.getElementById('chk_year').addEventListener('keydown', e => { if (e.key === 'Enter') runCheck(); });
document.getElementById('chk_make').addEventListener('input', e => { e.target.value = e.target.value.toUpperCase(); });
document.getElementById('chk_model').addEventListener('input', e => { e.target.value = e.target.value.toUpperCase(); });
</script>
@endpush
