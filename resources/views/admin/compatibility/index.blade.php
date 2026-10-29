{{-- FILE: resources/views/admin/compatibility/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Compatibility Checker')
@section('page-title', 'Compatibility Checker')
@section('page-sub', 'Powerlink-style interchange + Ladipo Tokunbo market algorithm — vehicle-centric search')

@section('content')

{{-- ── VIN Decode ───────────────────────────────────────────── --}}
<div class="bg-navy rounded-2xl p-5 mb-6">
    <div class="text-white font-display font-700 text-sm uppercase tracking-wide mb-1">🔍 VIN Auto-Decode</div>
    <p class="text-gray-400 text-xs font-body mb-3">Decode a VIN to auto-fill Make, Model and Year below.</p>
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

{{-- ── KPI Cards ───────────────────────────────────────────── --}}
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

{{-- ── Vehicle Lookup ──────────────────────────────────────── --}}
<div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Check Compatible Parts for a Vehicle</h3>
    <p class="text-xs text-gray-400 font-body mb-4">
        <strong>Tier 1</strong> Interchange groups →
        <strong>Tier 2</strong> Direct year-range match →
        <strong>Tier 2b</strong> Platform/chassis match (Suspension/Brakes only) →
        <strong>Tier 3</strong> OEM-code heuristic (Ladipo/Tokunbo market algorithm).
        Confirmed groups shown first.
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
                @for($y = date('Y'); $y >= 1990; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button onclick="runCheck()"
                class="bg-gold text-navy font-display font-700 text-sm py-2.5 px-5 rounded-xl hover:bg-yellow-400 transition-colors">
            Find Compatible Parts
        </button>
        <button onclick="getVehicleAiSuggestions()" id="aiVehicleSuggestBtn"
                class="bg-navy text-white font-display font-700 text-sm py-2.5 px-5 rounded-xl hover:bg-opacity-90 transition-colors ml-2">
            🤖 Get AI Suggestions
        </button>
    </div>
    <div class="mt-3 grid grid-cols-3 gap-3">
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
        <div>
            {{-- NEW: Sub Model / Trim — matches RAPID XLParts' own
                 pattern. Soft narrowing only (see CompatibilityController
                 comment) — leaving this blank behaves exactly as before. --}}
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">Trim / Sub Model (optional)</label>
            <input type="text" id="chk_trim"
                   placeholder="e.g. LE, LE Eco, SE..."
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1.5">How wide to search</label>
            <select id="chk_breadth"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="standard">Standard (confirmed groups, direct, chassis)</option>
                <option value="verified">Verified only (what customers see)</option>
                <option value="broad">Broad (adds same engine/gearbox family and near years)</option>
            </select>
        </div>
    </div>

    {{-- Engine picker — appears only when the selected vehicle has more
         than one known engine option and none has been chosen yet.
         Prevents the system from silently guessing one engine (e.g. the
         4-cyl) and hiding that a V6 option even exists. --}}
    <div id="enginePickerWrap" class="hidden mt-3 bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="text-xs font-700 text-amber-800 uppercase tracking-wide mb-1">⚠ This vehicle has more than one engine option</div>
        <p class="text-xs text-amber-700 mb-2">Pick the one that matches the physical part/vehicle so pin count and displacement shown below are accurate — not a guess.</p>
        <div id="enginePickerOptions" class="flex flex-wrap gap-2"></div>
    </div>

    <input type="hidden" id="chk_cylinders" value="">
    <input type="hidden" id="chk_engine_l" value="">


    <div id="checkLoading" class="mt-4 hidden text-sm text-gray-400 text-center py-4">
        <div class="animate-pulse">Searching interchange groups + Ladipo OEM database...</div>
    </div>
    <div id="checkEmpty" class="mt-4 hidden text-sm text-gray-400 text-center py-6 bg-gray-50 rounded-xl">
        No compatible parts found in stock for that vehicle.
        <div class="text-xs text-gray-400 mt-1">Check the interchange groups below or see the powertrain/platform panels for alternatives.</div>
    </div>

    <div id="checkResults" class="mt-5 hidden">
        <div id="checkCount" class="text-sm font-600 text-navy mb-3 flex items-center gap-2"></div>
        <div class="flex gap-2 mb-3 text-xs flex-wrap">
            <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 font-700">✓ Interchange = Confirmed group</span>
            <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-700">Direct = Year-range match</span>
            <span class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 font-700">⚙ Platform = Suspension/Brakes chassis match</span>
            <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-700">~ Heuristic = OEM suggestion</span>
            <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700 font-700">Family = same engine/gearbox (Broad mode)</span>
            <span class="px-2 py-1 rounded-full bg-gray-200 text-gray-700 font-700">Near year = within 2 years (Broad mode)</span>
        </div>
        {{-- Sort and narrow the results on screen: trim, drive, cylinders, engine size, price... --}}
        <div id="resultControls" class="flex flex-wrap gap-2 items-end mb-3 bg-gray-50 border border-gray-200 rounded-xl p-3">
            <div>
                <label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Sort by</label>
                <select id="rc_sort" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white">
                    <option value="best">Best match first</option>
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                    <option value="trim">Trim (A-Z)</option>
                    <option value="drive">Drive type</option>
                    <option value="cyl">Cylinders</option>
                    <option value="size">Engine size</option>
                    <option value="year_new">Year: newest</option>
                    <option value="year_old">Year: oldest</option>
                    <option value="grade">Condition (best first)</option>
                    <option value="location">Location (A-Z)</option>
                    <option value="name">Part name (A-Z)</option>
                </select>
            </div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Trim</label>
                <select id="rc_trim" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white"></select></div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Drive</label>
                <select id="rc_drive" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white"></select></div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Cylinders</label>
                <select id="rc_cyl" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white"></select></div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Engine size</label>
                <select id="rc_size" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white"></select></div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Location</label>
                <select id="rc_loc" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white"></select></div>
            <div><label class="block text-[10px] text-gray-500 uppercase tracking-wide mb-1">Match type</label>
                <select id="rc_src" onchange="renderResults()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white">
                    <option value="">All</option>
                    <option value="verified">Verified fits only</option>
                    <option value="interchange">Interchange groups</option>
                    <option value="direct">Direct match</option>
                    <option value="platform">Chassis match</option>
                    <option value="family">Engine/gearbox family</option>
                    <option value="near_year">Near year</option>
                </select></div>
            <button type="button" onclick="resetResultControls()" class="text-xs text-gray-500 underline pb-2">Reset</button>
        </div>
        <div id="checkList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4"></div>

        <div id="suggestionsWrap" class="hidden mt-4">
            <div class="text-xs font-600 text-gray-500 uppercase tracking-wide mb-2">
                💡 Ladipo/OEM Heuristic Suggestions (auto-detected — not yet confirmed)
            </div>
            <div id="suggestionsList" class="space-y-2"></div>
        </div>
    </div>

    {{-- ── AI Vehicle Suggestions (covers ALL part categories — headlights,
         doors, brake pads, steering racks, etc — not just engine/trans) ── --}}
    <div id="aiVehicleResults" class="mt-5 hidden">
        <div id="aiVehicleLoading" class="hidden text-sm text-gray-400 text-center py-4">
            <div class="animate-pulse">Asking AI which related vehicles share parts with this one...</div>
        </div>

        <div id="aiVehicleContent" class="hidden">
            <div class="text-xs font-600 text-gray-500 uppercase tracking-wide mb-2">
                🤖 AI-Suggested Related Vehicles <span class="font-normal normal-case text-gray-400">(shares platform/engine/body — review before relying on)</span>
            </div>
            <div id="aiVehicleList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-4"></div>

            <div id="aiStockMatchWrap" class="hidden">
                <div class="text-xs font-600 text-green-700 uppercase tracking-wide mb-2 mt-4">
                    ✓ Matching Stock Found <span class="font-normal normal-case text-gray-400">(parts you already have that fit these related vehicles — any category)</span>
                </div>
                <div id="aiStockMatchList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2"></div>
            </div>

            <div id="aiVehicleEmpty" class="hidden text-xs text-gray-400 text-center py-4 bg-gray-50 rounded-xl">
                No confident AI suggestions for this vehicle.
            </div>
        </div>
    </div>
</div>

{{-- ── Interchange Group Library ─────────────────────────── --}}
<div class="flex items-start justify-between mb-4">
    <div>
        <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Interchange Group Library</h3>
        <p class="text-xs text-gray-400 mt-0.5">All confirmed interchange groups across your entire inventory — not filtered by the search above.</p>
    </div>
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
                <th class="px-4 py-3 text-center text-xs font-display uppercase tracking-wide">In Stock</th>
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
vinInput.addEventListener('keydown', e => { if(e.key==='Enter') decodeVin(); });
vinBtn.addEventListener('click', decodeVin);

async function decodeVin() {
    const vin = vinInput.value.trim();
    if(vin.length!==17){showVinStatus('VIN must be exactly 17 characters.','error');return;}
    vinBtn.textContent='Decoding...'; vinBtn.disabled=true;
    try {
        const res = await fetch('{{ route('admin.harvest.vin-decode') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body:JSON.stringify({vin})
        });
        const data = await res.json();
        if(data.error||!data.vehicle){showVinStatus(data.error||'Could not decode VIN.','error');return;}
        const v = data.vehicle;
        document.getElementById('chk_make').value = (v.make||'').toUpperCase();
        await loadCheckModels((v.make||'').toUpperCase());
        document.getElementById('chk_model').value = (v.model||'').toUpperCase();
        document.getElementById('chk_year').value  = v.year||'';
        showVinStatus(`✓ Decoded: ${v.year} ${v.make} ${v.model}. Click "Find Compatible Parts".`,'success');
    } catch(e){ showVinStatus('Network error.','error'); }
    finally{ vinBtn.textContent='DECODE VIN'; vinBtn.disabled=false; }
}

function showVinStatus(msg,type){
    vinStatus.textContent=msg;
    vinStatus.className=`mt-2 text-xs font-body ${type==='success'?'text-green-400':'text-red-400'}`;
    vinStatus.classList.remove('hidden');
}

// ── MODEL CASCADE ───────────────────────────────────────────────
async function loadCheckModels(make) {
    const sel = document.getElementById('chk_model');
    if(!make){sel.innerHTML='<option value="">Select Make first</option>';return;}
    sel.innerHTML='<option value="">Loading...</option>';
    resetEngineSelection();
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make)}`);
        const data = await res.json();
        sel.innerHTML='<option value="">Select Model</option>'+(data.models||[]).map(m=>`<option value="${m}">${m}</option>`).join('');
    } catch(e){ sel.innerHTML='<option value="">Could not load models</option>'; }
}

// ── ENGINE PICKER ────────────────────────────────────────────────
function selectEngineOption(cylinders, engineL) {
    document.getElementById('chk_cylinders').value = cylinders || '';
    document.getElementById('chk_engine_l').value  = engineL || '';
    document.getElementById('enginePickerWrap').classList.add('hidden');
    runCheck();
}

function resetEngineSelection() {
    document.getElementById('chk_cylinders').value = '';
    document.getElementById('chk_engine_l').value  = '';
    document.getElementById('enginePickerWrap')?.classList.add('hidden');
}
document.getElementById('chk_model').addEventListener('change', resetEngineSelection);

// ── COMPATIBILITY CHECK ─────────────────────────────────────────
// ── Result list: sort and narrow on screen ───────────────────────────
function escHtml(x){return String(x==null?'':x).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
const GRADE_RANK = {'New':0,'A':1,'B':2,'C':3};

function fillSelect(id, label, values, fmt){
    const el = document.getElementById(id); if(!el) return;
    const keep = el.value;
    el.innerHTML = `<option value="">${label}</option>` + values.map(v=>`<option value="${escHtml(v)}">${escHtml(fmt?fmt(v):v)}</option>`).join('');
    if ([...el.options].some(o=>o.value===keep)) el.value = keep;
}
function setupResultControls(){
    const r = window.__chkResults || [];
    const uniq = (f)=>[...new Set(r.map(f).filter(v=>v!==null&&v!==undefined&&v!==''))];
    fillSelect('rc_trim','Any trim', uniq(p=>p.donor_trim).sort());
    fillSelect('rc_drive','Any drive', uniq(p=>p.drive_type).sort());
    fillSelect('rc_cyl','Any', uniq(p=>p.cylinders).sort((a,b)=>a-b), v=>v+' cyl');
    fillSelect('rc_size','Any size', uniq(p=>p.engine_l).sort((a,b)=>a-b), v=>Number(v).toFixed(1)+'L');
    fillSelect('rc_loc','All locations', uniq(p=>p.location).sort());
}
function resetResultControls(){
    ['rc_trim','rc_drive','rc_cyl','rc_size','rc_loc','rc_src'].forEach(id=>{const e=document.getElementById(id); if(e) e.value='';});
    const s=document.getElementById('rc_sort'); if(s) s.value='best';
    renderResults();
}
function sortedFilteredResults(){
    let list = (window.__chkResults||[]).slice();
    const val = id => document.getElementById(id)?.value || '';
    const trim=val('rc_trim'), drive=val('rc_drive'), cyl=val('rc_cyl'), size=val('rc_size'), loc=val('rc_loc'), src=val('rc_src');
    list = list.filter(p=>{
        if (trim && p.donor_trim !== trim) return false;
        if (drive && p.drive_type !== drive) return false;
        if (cyl && String(p.cylinders) !== cyl) return false;
        if (size && String(p.engine_l) !== size) return false;
        if (loc && p.location !== loc) return false;
        if (src === 'verified' && !p.is_verified) return false;
        if (src && src !== 'verified' && p.source !== src) return false;
        return true;
    });
    const sort = val('rc_sort') || 'best';
    // nulls always go last, so "Trim A-Z" lists the parts with a trim first
    const cmp = (get, dir=1) => (a,b)=>{
        const x=get(a), y=get(b), nx=(x===null||x===undefined||x===''), ny=(y===null||y===undefined||y==='');
        if (nx&&ny) return 0; if (nx) return 1; if (ny) return -1;
        return (typeof x==='number'&&typeof y==='number' ? x-y : String(x).localeCompare(String(y))) * dir;
    };
    const by = {
        best:      (a,b)=>(a.tier||9)-(b.tier||9) || a.price_num-b.price_num,
        price_asc: cmp(p=>p.price_num), price_desc: cmp(p=>p.price_num,-1),
        trim: cmp(p=>p.donor_trim), drive: cmp(p=>p.drive_type), cyl: cmp(p=>p.cylinders), size: cmp(p=>p.engine_l),
        year_new: cmp(p=>p.year_to,-1), year_old: cmp(p=>p.year_from),
        grade: cmp(p=>GRADE_RANK[p.grade]??9), location: cmp(p=>p.location), name: cmp(p=>p.part_name),
    };
    return list.sort(by[sort] || by.best);
}
function renderResults(){
    const all = window.__chkResults || [];
    const list = sortedFilteredResults();
    const cnt = document.getElementById('checkCount');
    if (cnt) cnt.innerHTML = `<span class="bg-navy text-gold font-mono font-700 px-3 py-1 rounded-full text-sm">${list.length}</span>
        ${list.length===all.length?'':`<span class="text-xs text-gray-400">of ${all.length}</span>`} compatible part(s) in stock for <strong>${escHtml(window.__chkSearch||'')}</strong>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-700 uppercase">${escHtml(window.__chkBreadth||'standard')}</span>`;
    const box = document.getElementById('checkList');
    if (!list.length) { box.innerHTML = '<div class="col-span-full text-sm text-gray-400 text-center py-6 bg-gray-50 rounded-xl">Nothing matches those filters. Reset them or widen the search.</div>'; return; }
    box.innerHTML = list.map(p=>{
        const chips = [p.donor_trim, p.drive_type, p.cylinders?p.cylinders+' cyl':null, p.engine_l?Number(p.engine_l).toFixed(1)+'L':null, p.engine_code].filter(Boolean);
        const yrs = p.year_from ? `${p.year_from}${p.year_to&&p.year_to!=p.year_from?'-'+p.year_to:''}` : '';
        const fitCls = p.is_verified ? 'bg-green-100 text-green-800' : (p.tier>=5 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600');
        return `
        <div class="border border-gray-200 rounded-xl p-3 hover:border-yellow-400 transition-colors">
            ${p.photo
                ?`<img src="${p.photo}" class="w-full h-28 object-cover rounded-lg mb-2" onerror="this.src='/images/parts-photo-coming-soon.jpg'">`
                :`<div class="w-full h-28 bg-gray-100 rounded-lg mb-2 flex items-center justify-center text-gray-300 text-xs">No photo</div>`}
            <div class="font-600 text-sm text-navy">${escHtml(p.part_name)}</div>
            ${yrs?`<div class="text-[10px] text-gray-500">${escHtml(yrs)}</div>`:''}
            ${chips.length?`<div class="flex flex-wrap gap-1 mt-1">${chips.map(c=>`<span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">${escHtml(c)}</span>`).join('')}</div>`:''}
            <div class="font-mono text-[10px] text-gray-400 mt-1">${escHtml(p.part_code)}</div>
            ${p.fits_vehicles?`<div class="text-[10px] text-blue-500 mt-1">Fits ${p.fits_vehicle_count} model${p.fits_vehicle_count===1?'':'s'}: ${escHtml(p.fits_vehicles)}</div>`:''}
            <div class="flex items-center justify-between mt-2">
                <div>
                    <span class="font-700 text-gold text-sm">${escHtml(p.price)}</span>
                    ${p.price_wholesale?`<span class="text-[10px] text-gray-400 ml-1">/ ${escHtml(p.price_wholesale)} trade</span>`:''}
                </div>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-700">Grade ${escHtml(p.grade)}</span>
            </div>
            ${p.combined_stock>1?`<div class="text-[10px] text-blue-600 mt-1">Interchange stock: ${p.combined_stock} units</div>`:''}
            <div class="text-[10px] text-gray-400 mt-1">${escHtml(p.location)}${p.bin?' · '+escHtml(p.bin):''}</div>
            <div class="flex gap-1 mt-2 flex-wrap">
                ${p.fit_label?`<span class="text-[9px] px-1.5 py-0.5 rounded font-700 ${fitCls}">${p.is_verified?'✓ ':''}${escHtml(p.fit_label)}</span>`:''}
                ${p.source==='interchange'?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-700">Interchange</span>`:''}
                ${p.source==='direct'?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-700">Direct match</span>`:''}
                ${p.source==='platform'?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-700">⚙ Chassis match</span>`:''}
                ${p.source==='family'?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 font-700">Family match</span>`:''}
                ${p.source==='near_year'?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 font-700">Near year</span>`:''}
                ${p.major_component?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-700">⚡ Major</span>`:''}
                ${p.legal_trace?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-700">⚠ Legal Trace</span>`:''}
                ${p.group_code?`<span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-mono">IC# ${escHtml(p.group_code)}</span>`:''}
            </div>
            <a href="/admin/inventory/${p.id}" class="block text-center text-xs text-gold border border-gold/30 rounded-lg py-1.5 mt-2 hover:bg-gold/10 transition-colors">
                View / Edit Part →
            </a>
        </div>`;}).join('');
}

async function runCheck() {
    const make     = document.getElementById('chk_make').value.trim();
    const model    = document.getElementById('chk_model').value.trim();
    const year     = document.getElementById('chk_year').value.trim();
    const partName = document.getElementById('chk_part')?.value.trim()||'';
    const category = document.getElementById('chk_category')?.value.trim()||'';
    const trim     = document.getElementById('chk_trim')?.value.trim()||'';
    const cylinders= document.getElementById('chk_cylinders')?.value||'';
    const engineL  = document.getElementById('chk_engine_l')?.value||'';
    if(!make||!model||!year){alert('Please select Make, Model and Year.');return;}

    ['checkResults','checkEmpty','suggestionsWrap'].forEach(id=>document.getElementById(id)?.classList.add('hidden'));
    // Remove previous reference panels
    document.getElementById('refPanel')?.remove();
    document.getElementById('platformRefPanel')?.remove();
    document.getElementById('checkLoading').classList.remove('hidden');

    try {
        const res  = await fetch('{{ route('admin.compatibility.check') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body:JSON.stringify({make,model,year,part_name:partName,category,trim,cylinders,engine_l:engineL,breadth:document.getElementById('chk_breadth')?.value||'standard'}),
        });
        const data = await res.json();
        document.getElementById('checkLoading').classList.add('hidden');

        // Engine picker — show only if this vehicle has multiple known
        // engines and staff hasn't picked one yet for THIS search.
        const pickerWrap = document.getElementById('enginePickerWrap');
        if (data.engine_options && data.engine_options.length > 1 && !data.engine_disambiguated) {
            document.getElementById('enginePickerOptions').innerHTML = data.engine_options.map(opt => `
                <button type="button" onclick="selectEngineOption(${opt.cylinders}, ${opt.engine_l})"
                    class="bg-white border border-amber-300 text-amber-800 text-xs font-600 px-3 py-2 rounded-lg hover:bg-amber-100 transition-colors">
                    ${opt.label}${opt.pin_count?` · ${opt.pin_count}-pin`:''}
                </button>`).join('');
            pickerWrap.classList.remove('hidden');
        } else {
            pickerWrap.classList.add('hidden');
        }

        // Always show powertrain reference if available
        if(data.interchange_reference && data.interchange_reference.length>0){
            const div=document.createElement('div');
            div.id='refPanel';
            div.className='mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4';
            div.innerHTML=`
                <div class="text-xs font-700 text-navy uppercase tracking-wide mb-1">
                    🔗 Vehicles Sharing Same Powertrain
                    ${data.oem?.engine_code?`<span class="font-mono text-gold ml-2">${data.oem.engine_code}</span>`:''}
                    ${data.oem?.transmission_code?`<span class="font-mono text-gold ml-1">/ ${data.oem.transmission_code}</span>`:''}
                </div>
                <div class="flex flex-wrap gap-3 text-[11px] text-gray-500 mb-2">
                    ${data.oem?.engine_l?`<span>📏 Displacement: <strong class="text-navy">${data.oem.engine_l}L</strong></span>`:''}
                    ${data.oem?.cylinders?`<span>🔧 Cylinders: <strong class="text-navy">${data.oem.cylinders}</strong></span>`:''}
                    ${data.oem?.drive_type?`<span>⚙ Drive: <strong class="text-navy">${data.oem.drive_type}</strong></span>`:''}
                    ${data.oem?.pin_count?`<span>🔌 Pin count: <strong class="text-navy">${data.oem.pin_count}-pin</strong>${data.oem.pin_count_variants&&data.oem.pin_count_variants.length?` <span class="text-amber-600">(also seen: ${data.oem.pin_count_variants.join('/')}-pin — confirm on unit)</span>`:''}</span>`:''}
                </div>
                <p class="text-xs text-gray-400 mb-2">Same engine/transmission — parts are interchangeable. Use to advise customers of alternatives even with zero stock.</p>
                <div class="flex flex-wrap gap-2">
                    ${data.interchange_reference.map(ref=>`<span class="bg-white border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">${ref}</span>`).join('')}
                </div>`;
            document.getElementById('checkEmpty').after(div);
        }

        // Platform/chassis reference — shown even with zero stock, same
        // pattern as the powertrain panel above.
        //
        // FIXED (2nd pass): an earlier attempted fix blanket-suppressed
        // this panel for any category other than Suspension/Brakes —
        // which wrongly hid legitimate "own generation" entries (e.g.
        // 2014-2019 Corolla, own model/generation) that DO cover Body/
        // Interior/etc, since those aren't a cross-model claim at all.
        // Now uses the backend's pre-filtered, type-tagged entries list
        // (data.platform.entries) — only entries whose own categories
        // actually include what was searched, correctly distinguishing
        // "own generation" (broad) from genuine cross-model chassis-
        // mates (Suspension/Brakes only).
        const searchedLabel = data.searched_part_name
            ? `${data.searched_part_name}${data.searched_category ? ' (' + data.searched_category + ')' : ''}`
            : (data.searched_category || null);

        if (data.platform && data.platform.generation && data.platform.entries && data.platform.entries.length) {
            const ownGen = data.platform.entries.filter(e => e.type === 'own_generation');
            const crossModel = data.platform.entries.filter(e => e.type === 'cross_model');

            const pdiv=document.createElement('div');
            pdiv.id='platformRefPanel';
            pdiv.className='mt-4 bg-purple-50 border border-purple-200 rounded-xl p-4';
            pdiv.innerHTML=`
                <div class="text-xs font-700 text-navy uppercase tracking-wide mb-1">
                    ⚙ Chassis Platform: <span class="font-mono text-purple-700">${data.platform.generation}</span>
                    ${data.platform.body_style?`<span class="text-gray-400 ml-1">(${data.platform.body_style})</span>`:''}
                    ${searchedLabel ? `<span class="text-gray-400 font-normal normal-case ml-2">— for ${searchedLabel}</span>` : ''}
                </div>
                ${ownGen.length ? `
                <p class="text-xs text-green-700 mb-1">✓ Same model, same generation — ${category || 'this category'} genuinely applies:</p>
                <div class="flex flex-wrap gap-2 mb-2">
                    ${ownGen.map(e=>`<span class="bg-white border border-green-200 text-green-700 text-xs px-3 py-1.5 rounded-full">${e.label}</span>`).join('')}
                </div>` : ''}
                ${crossModel.length ? `
                <p class="text-xs text-gray-500 mb-1">⚠ Different model, same chassis — Suspension/Brakes only, needs visual/physical confirmation for anything else:</p>
                <div class="flex flex-wrap gap-2">
                    ${crossModel.map(e=>`<span class="bg-white border border-purple-200 text-purple-700 text-xs px-3 py-1.5 rounded-full">${e.label}</span>`).join('')}
                </div>` : ''}`;
            document.getElementById('checkEmpty').after(pdiv);
        } else if (data.platform && data.platform.generation && category) {
            // Platform exists but NOTHING in it applies to the searched
            // category (e.g. searched "Electrical" on a platform where
            // only Suspension/Brakes cross-model entries exist, no own-
            // generation entry either) — say so plainly instead of
            // showing an irrelevant panel or silently showing nothing.
            const ndiv=document.createElement('div');
            ndiv.id='platformRefPanel';
            ndiv.className='mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4';
            ndiv.innerHTML=`
                <div class="text-xs font-700 text-gray-500 uppercase tracking-wide mb-1">
                    ${searchedLabel || category} — no platform-level claim available
                </div>
                <p class="text-xs text-gray-500">
                    This chassis platform (<span class="font-mono">${data.platform.generation}</span>) has no confirmed
                    ${category.toLowerCase()} sharing recorded. Any match needs a confirmed Interchange Group or a
                    direct year-range match on the SAME model.
                </p>`;
            document.getElementById('checkEmpty').after(ndiv);
        }

        if(!data.count){
            const emptyDiv = document.getElementById('checkEmpty');
            emptyDiv.innerHTML = searchedLabel
                ? `No compatible <strong>${searchedLabel}</strong> found in stock for <strong>${data.search}</strong>.
                   <div class="text-xs text-gray-400 mt-1">Check the interchange groups below or see the powertrain/platform panels for alternatives.</div>`
                : `No compatible parts found in stock for that vehicle.
                   <div class="text-xs text-gray-400 mt-1">Check the interchange groups below or see the powertrain/platform panels for alternatives.</div>`;
            emptyDiv.classList.remove('hidden');
            return;
        }

        document.getElementById('checkCount').innerHTML=`
            <span class="bg-navy text-gold font-mono font-700 px-3 py-1 rounded-full text-sm">${data.count}</span>
            compatible part(s) in stock for <strong>${data.search}</strong>`;

        window.__chkResults = data.results || [];
        window.__chkBreadth = data.breadth || 'standard';
        window.__chkSearch = data.search || '';
        setupResultControls();
        renderResults();

        document.getElementById('checkResults').classList.remove('hidden');

        if(data.suggestions&&data.suggestions.length>0){
            document.getElementById('suggestionsList').innerHTML=data.suggestions.map(s=>{
                // e.g. "2GR-FE · 3.5L · AWD" — matches the format asked
                // for, shown once per suggestion group rather than
                // repeated per vehicle.
                const driveLabel = s.drive_type ? ` · ${s.drive_type}` : '';
                const vehicleList = (s.vehicles||[]).map(v =>
                    typeof v === 'string' ? v : v.label
                ).join(', ');
                return `
                <div class="border border-yellow-200 bg-yellow-50 rounded-xl px-4 py-3">
                    <div class="text-xs font-700 text-yellow-800">${s.part_name} — OEM: ${s.engine_code}${driveLabel}</div>
                    <div class="text-[10px] text-yellow-600 mt-0.5">Also likely fits: ${vehicleList}</div>
                    ${s.part_category==='Transmission' ? `<div class="text-[9px] text-yellow-500 mt-1">⚙ Drive-type mismatches already excluded — transmissions/axles don't cross FWD/AWD.</div>` : ''}
                </div>`;
            }).join('');
            document.getElementById('suggestionsWrap').classList.remove('hidden');
        }

    } catch(e){
        document.getElementById('checkLoading').classList.add('hidden');
        alert('Check failed. Please try again.');
    }
}

document.getElementById('chk_breadth')?.addEventListener('change', () => {
    if(document.getElementById('chk_make').value && document.getElementById('chk_model').value && document.getElementById('chk_year').value) runCheck();
});
document.getElementById('chk_year').addEventListener('change', e => {
    if(document.getElementById('chk_make').value && document.getElementById('chk_model').value) runCheck();
});

// ── AI Vehicle Suggestions (Compatibility Checker) ───────────────
async function getVehicleAiSuggestions() {
    const make  = document.getElementById('chk_make').value.trim();
    const model = document.getElementById('chk_model').value.trim();
    const year  = document.getElementById('chk_year').value.trim();
    const partName = document.getElementById('chk_part')?.value.trim() || '';
    if(!make || !model || !year) { alert('Please select Make, Model and Year first.'); return; }

    const btn = document.getElementById('aiVehicleSuggestBtn');
    btn.disabled = true;
    btn.textContent = '🤖 Thinking...';

    document.getElementById('aiVehicleResults').classList.remove('hidden');
    document.getElementById('aiVehicleContent').classList.add('hidden');
    document.getElementById('aiVehicleEmpty').classList.add('hidden');
    document.getElementById('aiVehicleLoading').classList.remove('hidden');

    try {
        const res = await fetch(`{{ route('admin.compatibility.ai-suggest') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ make, model, year, part_name: partName }),
        });
        const data = await res.json();
        document.getElementById('aiVehicleLoading').classList.add('hidden');

        if (data.error) {
            document.getElementById('aiVehicleEmpty').textContent = data.error;
            document.getElementById('aiVehicleEmpty').classList.remove('hidden');
            return;
        }

        const vehicles = data.vehicles || [];
        const stock     = data.matching_stock || [];

        if (vehicles.length === 0) {
            document.getElementById('aiVehicleEmpty').classList.remove('hidden');
            return;
        }

        document.getElementById('aiVehicleContent').classList.remove('hidden');
        document.getElementById('aiVehicleList').innerHTML = vehicles.map(v => `
            <div class="border border-gray-200 rounded-xl p-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="text-sm font-700 text-navy">${v.brand} ${v.model} ${v.year_from}${v.year_to !== v.year_from ? '–' + v.year_to : ''}</div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-700 whitespace-nowrap ${v.confidence==='high'?'bg-green-100 text-green-700':v.confidence==='medium'?'bg-amber-100 text-amber-700':'bg-gray-100 text-gray-500'}">${v.confidence}</span>
                </div>
                ${(v.engine_code || v.transmission_code) ? `<div class="text-[10px] font-mono text-gray-400 mt-1">${v.engine_code || ''}${v.engine_code && v.transmission_code ? ' / ' : ''}${v.transmission_code || ''}</div>` : ''}
                <div class="text-xs text-gray-400 mt-1">${v.reason}</div>
            </div>`).join('');

        if (stock.length > 0) {
            document.getElementById('aiStockMatchWrap').classList.remove('hidden');
            document.getElementById('aiStockMatchList').innerHTML = stock.map(p => `
                <a href="/admin/inventory/${p.id}/edit" class="block border border-green-200 bg-green-50 rounded-xl p-3 hover:border-green-400 transition-colors">
                    <div class="text-sm font-700 text-navy">${p.part_name}</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">${p.brand} ${p.model} ${p.year_from}${p.year_to !== p.year_from ? '–'+p.year_to : ''} · ${p.part_code}</div>
                    <div class="flex items-center justify-between mt-1.5">
                        <span class="text-xs font-700 text-gold">${p.currency_code === 'NGN' ? '₦' : '$'}${Number(p.price_local).toLocaleString()}</span>
                        <span class="text-[10px] text-gray-400">${p.location}</span>
                    </div>
                </a>`).join('');
        } else {
            document.getElementById('aiStockMatchWrap').classList.add('hidden');
        }

    } catch (e) {
        document.getElementById('aiVehicleLoading').classList.add('hidden');
        document.getElementById('aiVehicleEmpty').textContent = 'Request failed — please try again.';
        document.getElementById('aiVehicleEmpty').classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = '🤖 Get AI Suggestions';
    }
}
</script>
@endpush
