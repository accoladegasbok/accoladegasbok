{{-- FILE: resources/views/admin/compatibility/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Compatibility Checker')
@section('page-title', 'Compatibility Checker')
@section('page-sub', 'Powerlink-style interchange + Ladipo Tokunbo market algorithm — vehicle-centric search')

@section('content')

{{-- ── VIN Decode (admin side) ────────────────────────────────────── --}}
<div class="bg-navy rounded-2xl p-5 mb-6">
    <div class="text-white font-display font-700 text-sm uppercase tracking-wide mb-1">🔍 VIN Auto-Decode</div>
    <p class="text-gray-400 text-xs font-body mb-3">Decode a VIN to auto-fill Make, Model, Year and OEM codes for the compatibility search.</p>
    <div class="flex gap-2 max-w-2xl">
        <div class="flex-1 relative">
            <input type="text" id="vinInput" maxlength="17"
                   placeholder="Enter 17-digit VIN..."
                   class="w-full px-4 py-3 rounded-xl text-sm font-mono uppercase tracking-widest border border-white/20 bg-white/10 text-white placeholder:text-gray-500 placeholder:normal-case placeholder:tracking-normal focus:outline-none focus:border-gold">
            <span id="vinCharCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500">0/17</span>
        </div>
        <button type="button" id="vinDecodeBtn"
                class="bg-gold text-navy font-display font-700 text-sm px-5 py-3 rounded-xl hover:bg-yellow-400 transition-colors whitespace-nowrap">
            DECODE VIN
        </button>
    </div>
    <div id="vinStatus" class="mt-2 text-xs font-body hidden"></div>
</div>

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
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Confirmed (Manual)</div>
        <div class="font-display font-700 text-green-600 text-2xl">{{ number_format($manualGroups) }}</div>
    </div>
</div>

{{-- ── VEHICLE LOOKUP ───────────────────────────────────────────── --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Check Compatible Parts for a Vehicle</h3>
    <p class="text-xs text-gray-400 font-body mb-4">
        Resolution: <strong>Tier 1</strong> Interchange groups → <strong>Tier 2</strong> Direct year-range match →
        <strong>Tier 3</strong> OEM-code heuristic (Ladipo/Tokunbo market algorithm). Confirmed groups shown first.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Make</label>
            <select id="chk_make" onchange="loadCheckModels(this.value)"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="">Select Make</option>
                @foreach(\App\Data\VehicleDatabase::makes() as $brand)
                <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Model</label>
            <select id="chk_model"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="">Select Make first</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Year</label>
            <select id="chk_year"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="">Select Year</option>
                @for($y = date('Y'); $y >= 1995; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button onclick="runCheck()"
                class="bg-gold text-navy font-display font-700 text-sm py-2.5 px-5 rounded-xl hover:bg-yellow-400 transition-colors">
            Find Compatible Parts
        </button>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Part Name (optional)</label>
            <input type="text" id="chk_part" list="partNameList"
                   placeholder="Start typing — e.g. Starter, Alternator..."
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
            <datalist id="partNameList">
                @foreach(\App\Data\PartNames::flat() as $pn)
                <option value="{{ $pn }}">
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Category (optional)</label>
            <select id="chk_category"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="">All Categories</option>
                @foreach(['Engine','Transmission','Body','Suspension','Electrical','Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels'] as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div id="checkLoading" class="mt-4 hidden text-sm text-gray-400 text-center py-4">
        <div class="animate-pulse">Searching interchange groups + Ladipo OEM database...</div>
    </div>
    <div id="checkEmpty" class="mt-4 hidden text-sm text-gray-400 text-center py-6 bg-gray-50 rounded-xl">
        No compatible parts found in stock for that vehicle.
        <div class="text-xs text-gray-400 mt-1">Check the interchange groups below — a group may exist but have no available stock.</div>
    </div>

    <div id="checkResults" class="mt-5 hidden">
        <div id="checkCount" class="text-sm font-600 text-navy mb-3 flex items-center gap-2"></div>

        {{-- Tier labels --}}
        <div class="flex gap-2 mb-3 text-xs">
            <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 font-700">✓ Interchange = Confirmed group</span>
            <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-700">Direct = Year-range match</span>
            <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-700">~ Heuristic = OEM code suggestion</span>
        </div>

        <div id="checkList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4"></div>

        {{-- Heuristic suggestions --}}
        <div id="suggestionsWrap" class="hidden mt-4">
            <div class="text-xs font-600 text-gray-500 uppercase tracking-wide mb-2">
                💡 Ladipo/OEM Heuristic Suggestions (auto-detected via OEM code — not yet confirmed)
            </div>
            <div id="suggestionsList" class="space-y-2"></div>
            <p class="text-xs text-gray-400 mt-2">
                These parts share the same engine/transmission OEM code as this vehicle.
                Confirm compatibility on the inventory edit page to save as an interchange group.
            </p>
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
                <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Group Code (IC#)</th>
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
                    <span class="font-700 text-sm {{ $g->parts_available > 0 ? 'text-green-600' : 'text-gray-400' }}">{{ $g->parts_available }}</span>
                </td>
                <td class="px-4 py-3 text-center text-sm text-gray-500">{{ $g->parts_total }}</td>
                <td class="px-4 py-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700
                        {{ $g->source === 'manual' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $g->source === 'manual' ? '✓ Confirmed' : '~ Auto/Heuristic' }}
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
// ── VIN DECODE ─────────────────────────────────────────────────
const vinInput  = document.getElementById('vinInput');
const vinCount  = document.getElementById('vinCharCount');
const vinBtn    = document.getElementById('vinDecodeBtn');
const vinStatus = document.getElementById('vinStatus');

vinInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
    vinCount.textContent = `${this.value.length}/17`;
});

vinInput.addEventListener('keydown', e => { if (e.key === 'Enter') decodeVin(); });
vinBtn.addEventListener('click', decodeVin);

async function decodeVin() {
    const vin = vinInput.value.trim();
    if (vin.length !== 17) {
        showVinStatus('VIN must be exactly 17 characters.', 'error'); return;
    }
    vinBtn.textContent = 'Decoding...'; vinBtn.disabled = true;
    try {
        const res  = await fetch('{{ route('admin.harvest.vin-decode') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ vin })
        });
        const data = await res.json();
        if (data.error || !data.vehicle) { showVinStatus(data.error || 'Could not decode VIN.', 'error'); return; }
        const v = data.vehicle;
        document.getElementById('chk_make').value  = (v.make  || '').toUpperCase();
        await loadCheckModels((v.make || '').toUpperCase());
        document.getElementById('chk_model').value = (v.model || '').toUpperCase();
        document.getElementById('chk_year').value  = v.year || '';
        showVinStatus(`✓ Decoded: ${v.year} ${v.make} ${v.model}${v.oem_engine_code ? ' · ' + v.oem_engine_code : ''}. Click "Find Compatible Parts" to search.`, 'success');
    } catch (e) {
        showVinStatus('Network error decoding VIN.', 'error');
    } finally {
        vinBtn.textContent = 'DECODE VIN'; vinBtn.disabled = false;
    }
}

function showVinStatus(msg, type) {
    vinStatus.textContent = msg;
    vinStatus.className = `mt-2 text-xs font-body ${type === 'success' ? 'text-green-400' : 'text-red-400'}`;
    vinStatus.classList.remove('hidden');
}

// ── COMPATIBILITY CHECK ─────────────────────────────────────────
async function runCheck() {
    const make     = document.getElementById('chk_make').value.trim();
    const model    = document.getElementById('chk_model').value.trim();
    const year     = document.getElementById('chk_year').value.trim();
    const partName = document.getElementById('chk_part')?.value.trim() || '';
    const category = document.getElementById('chk_category')?.value.trim() || '';

    if (!make || !model || !year) { alert('Please select Make, Model and Year.'); return; }

    ['checkResults','checkEmpty','suggestionsWrap'].forEach(id => document.getElementById(id).classList.add('hidden'));
    document.getElementById('checkLoading').classList.remove('hidden');

    try {
        const res  = await fetch('{{ route('admin.compatibility.check') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ make, model, year, part_name: partName, category }),
        });
        const data = await res.json();
        document.getElementById('checkLoading').classList.add('hidden');

        // Always show interchange reference if available
        function showInterchangeRef(data) {
            if (data.interchange_reference && data.interchange_reference.length > 0) {
                const refHtml = `
                    <div class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs font-700 text-navy uppercase tracking-wide mb-1">
                            🔗 Vehicles Sharing Same Powertrain
                            ${data.oem?.engine_code ? `<span class="font-mono text-gold ml-2">${data.oem.engine_code}</span>` : ''}
                            ${data.oem?.transmission_code ? `<span class="font-mono text-gold ml-1">/ ${data.oem.transmission_code}</span>` : ''}
                        </div>
                        <p class="text-xs text-gray-400 mb-2">
                            These vehicles use the same engine/transmission — parts are interchangeable.
                            Use this to advise customers of alternatives even when we have zero stock for their exact vehicle.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            ${data.interchange_reference.map(ref => `
                                <span class="bg-white border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">${ref}</span>
                            `).join('')}
                        </div>
                    </div>`;
                document.getElementById('checkEmpty').insertAdjacentHTML('afterend', refHtml.replace('id="refPanel"','').replace('<div class="mt-4','<div id="refPanel" class="mt-4'));
                // simpler:
                let existing = document.getElementById('refPanel');
                if (existing) existing.remove();
                const div = document.createElement('div');
                div.id = 'refPanel';
                div.className = 'mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4';
                div.innerHTML = `
                    <div class="text-xs font-700 text-navy uppercase tracking-wide mb-1">
                        🔗 Vehicles Sharing Same Powertrain
                        ${data.oem?.engine_code ? `<span class="font-mono text-gold ml-2">${data.oem.engine_code}</span>` : ''}
                        ${data.oem?.transmission_code ? `<span class="font-mono text-gold ml-1">/ ${data.oem.transmission_code}</span>` : ''}
                    </div>
                    <p class="text-xs text-gray-400 mb-2">
                        These vehicles use the same engine/transmission — parts are interchangeable.
                        Use this to advise customers of alternatives even with zero stock for their exact vehicle.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        ${data.interchange_reference.map(ref => `<span class="bg-white border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">${ref}</span>`).join('')}
                    </div>`;
                document.getElementById('checkEmpty').after(div);
            }
        }

        if (!data.count) {
            document.getElementById('checkEmpty').classList.remove('hidden');
            showInterchangeRef(data);
            return;
        }

        document.getElementById('checkCount').innerHTML = `
            <span class="bg-navy text-gold font-mono font-700 px-3 py-1 rounded-full text-sm">${data.count}</span>
            compatible part(s) in stock for <strong>${data.search}</strong>`;

        document.getElementById('checkList').innerHTML = data.results.map(p => `
            <div class="border border-gray-200 rounded-xl p-3 hover:border-yellow-400 transition-colors">
                ${p.photo ? `<img src="${p.photo}" class="w-full h-28 object-cover rounded-lg mb-2" onerror="this.src='/images/parts-photo-coming-soon.jpg'">` :
                    `<div class="w-full h-28 bg-gray-100 rounded-lg mb-2 flex items-center justify-center text-gray-300 text-xs">No photo</div>`}
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
                ${p.combined_stock > 1 ? `<div class="text-[10px] text-blue-600 mt-1">Combined interchange stock: ${p.combined_stock} units</div>` : ''}
                <div class="text-[10px] text-gray-400 mt-1">${p.location}${p.bin ? ' · ' + p.bin : ''}</div>
                <div class="flex gap-1 mt-2 flex-wrap">
                    ${p.source === 'interchange' ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-700">✓ Interchange</span>` : ''}
                    ${p.source === 'direct' ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-700">Direct match</span>` : ''}
                    ${p.major_component ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-700">⚡ Major</span>` : ''}
                    ${p.legal_trace ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-700">⚠ Legal Trace</span>` : ''}
                    ${p.group_code ? `<span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-mono">IC# ${p.group_code}</span>` : ''}
                </div>
                <a href="/admin/inventory/${p.id}" class="block text-center text-xs text-gold border border-gold/30 rounded-lg py-1.5 mt-2 hover:bg-gold/10 transition-colors">
                    View / Edit Part →
                </a>
            </div>
        `).join('');

        showInterchangeRef(data);
        document.getElementById('checkResults').classList.remove('hidden');

        // Heuristic suggestions
        if (data.suggestions && data.suggestions.length > 0) {
            document.getElementById('suggestionsList').innerHTML = data.suggestions.map(s => `
                <div class="border border-yellow-200 bg-yellow-50 rounded-xl px-4 py-3">
                    <div class="text-xs font-700 text-yellow-800">${s.part_name} — OEM: ${s.engine_code}</div>
                    <div class="text-[10px] text-yellow-600 mt-0.5">Also likely fits: ${Array.isArray(s.vehicles) ? s.vehicles.join(', ') : s.vehicles}</div>
                    <div class="text-[10px] text-gray-500 mt-1">Go to inventory edit page → Confirm Interchange to save this as a confirmed group</div>
                </div>
            `).join('');
            document.getElementById('suggestionsWrap').classList.remove('hidden');
        }

    } catch (e) {
        document.getElementById('checkLoading').classList.add('hidden');
        alert('Check failed. Please try again.');
    }
}

document.getElementById('chk_year').addEventListener('change', e => { if (e.key === 'Enter') runCheck(); });

async function loadCheckModels(make) {
    const sel = document.getElementById('chk_model');
    if (!make) { sel.innerHTML = '<option value="">Select Make first</option>'; return; }
    sel.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make)}`);
        const data = await res.json();
        sel.innerHTML = '<option value="">Select Model</option>' +
            (data.models || []).map(m => `<option value="${m}">${m}</option>`).join('');
    } catch(e) {
        sel.innerHTML = '<option value="">Could not load models</option>';
    }
}
</script>
@endpush
