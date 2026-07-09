{{-- FILE: resources/views/admin/harvest/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Register Donor Vehicle')
@section('page-title', 'New Harvest')
@section('page-sub', 'Register a donor vehicle and start the parts harvesting checklist')

@section('content')
<div class="max-w-3xl">

  <div class="flex gap-2 mb-5">
    <button type="button" id="tabVin" class="harvest-tab-btn bg-navy text-white font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide">VIN DECODE</button>
    <button type="button" id="tabManual" class="harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors">MANUAL ENTRY</button>
    <button type="button" id="tabSearch" class="harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors">SEARCH EXISTING</button>
  </div>

  <div id="panelVin" class="harvest-tab-panel stat-card mb-6">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Decode the VIN</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Enter the 17-character VIN from the dashboard, door jamb, or title.</p>
    <div class="flex gap-2">
      <input type="text" id="vinInput" maxlength="17" placeholder="17-character VIN"
        class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm font-body font-mono uppercase tracking-widest focus:outline-none focus:border-gold"
        oninput="document.getElementById('vinCount').textContent=this.value.length+'/17'">
      <button onclick="decodeVin()" id="decodeBtn" class="bg-navy text-white font-display font-700 text-sm px-5 py-3 rounded-xl hover:bg-navy-light transition-colors flex items-center gap-2 whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Decode VIN
      </button>
    </div>
    <div class="flex justify-between mt-1.5">
      <span id="vinCount" class="text-xs text-gray-400 font-mono">0/17</span>
      <span id="vinStatus" class="text-xs font-body"></span>
    </div>
  </div>

  <div id="panelSearch" class="harvest-tab-panel stat-card mb-6 hidden">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Search Existing Donor Vehicles</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Find a donor vehicle already registered by make, model, VIN, or notes.</p>
    <div class="flex gap-2">
      <input type="text" id="donorSearchInput" placeholder="Search by make, model, VIN, or notes..."
        class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold">
      <button onclick="searchDonors()" class="border border-navy text-navy font-display font-700 text-sm px-5 py-3 rounded-xl hover:bg-navy hover:text-white transition-colors flex items-center gap-2 whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Search
      </button>
    </div>
    <div id="donorSearchResults" class="mt-3 space-y-2 hidden"></div>
  </div>

  <form method="POST" action="{{ route('admin.harvest.store') }}" id="donorForm">
    @csrf

    <div id="decodedBanner" class="hidden mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
      <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <div>
        <div id="decodedLabel" class="text-green-800 font-body font-500 text-sm"></div>
        <div id="decodedSub" class="text-green-600 text-xs font-body mt-0.5"></div>
      </div>
    </div>

    {{-- Step 2: Vehicle Details --}}
    <div class="stat-card mb-6">
      <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Step 2 — Vehicle Details</h2>
      <input type="hidden" name="vin" id="vinField">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Make *</label>
          <select name="make" id="f_make" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Make</option>
            @foreach(\App\Data\VehicleDatabase::makes() as $mk)
              <option value="{{ $mk }}" {{ old('make')===$mk?'selected':'' }}>{{ $mk }}</option>
            @endforeach
            <option value="UNIVERSAL" {{ old('make')==='UNIVERSAL'?'selected':'' }}>OTHER / NOT LISTED</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
          <select name="model" id="f_model" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Model</option>
            @if(old('model'))<option value="{{ old('model') }}" selected>{{ old('model') }}</option>@endif
          </select>
          <input type="text" id="f_model_custom" placeholder="Type model if not listed" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 mt-1.5 hidden">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year *</label>
          <select name="year" id="f_year" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Year</option>
            @foreach(\App\Data\VehicleDatabase::years() as $yr)
              <option value="{{ $yr }}" {{ (string) old('year') === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Trim</label>
          <input type="text" name="trim" id="f_trim" value="{{ old('trim') }}" placeholder="LE, Sport, EX-L..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Body Style</label>
          <input type="text" name="body_style" id="f_body" value="{{ old('body_style') }}" placeholder="Sedan, SUV, Coupe..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Engine</label>
          <select id="f_engineSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Make/Model/Year first</option>
          </select>
          <input type="text" name="engine" id="f_engine" value="{{ old('engine') }}" placeholder="e.g. 2.5L I4" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 mt-1.5 hidden">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Exterior Colour</label>
          <input type="text" name="colour" value="{{ old('colour') }}" placeholder="White, Silver, Black..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage</label>
          <input type="number" name="mileage" value="{{ old('mileage') }}" min="0" placeholder="65000 (optional)" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Vehicle Condition *</label>
          <select name="condition" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="Good" {{ old('condition','Good')==='Good'?'selected':'' }}>Good</option>
            <option value="Fair" {{ old('condition')==='Fair'?'selected':'' }}>Fair</option>
            <option value="Poor" {{ old('condition')==='Poor'?'selected':'' }}>Poor</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Source *</label>
          <select name="source" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            @foreach(['Auction','Insurance','Private Sale','Dealer','Other'] as $s)
            <option value="{{ $s }}" {{ old('source')===$s?'selected':'' }}>{{ $s }}</option>
            @endforeach
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Storage Location *</label>
          <select name="location" id="f_location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            @foreach($locations as $loc)
            <option value="{{ $loc }}" {{ old('location', $staffLocation && $staffLocation !== 'All' ? $staffLocation : '') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
            @endforeach
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Notes</label>
          <textarea name="notes" rows="2" placeholder="Any special notes about this vehicle..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>

    {{-- Step 3: Acquisition Costs & ROI Setup (Powerlink: VEHICLE_COST_PAYMENT) --}}
    <div class="stat-card mb-6" style="border-left:4px solid #C8960C;">
      <div class="flex items-center gap-2 mb-1">
        <h2 class="font-display font-700 text-navy text-base tracking-wide uppercase">Step 3 — Acquisition Costs &amp; ROI Setup</h2>
        <span class="text-[10px] bg-gold/20 text-gold border border-gold/30 px-2 py-0.5 rounded-full font-700 uppercase tracking-wide">New</span>
      </div>
      <p class="text-xs text-gray-400 font-body mb-5">
        Break down what this vehicle cost to acquire. AutoZenith uses this to track ROI and show break-even progress as parts sell.
        All fields optional — fill in what you know now, edit the rest later via the vehicle record.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Purchase / Salvage Cost</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm cost-sym">₦</span>
            <input type="number" name="salvage_cost" id="c_salvage" min="0" step="any" value="{{ old('salvage_cost', 0) }}" oninput="recalcTotal()"
              class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400" placeholder="0">
          </div>
          <p class="text-[10px] text-gray-400 mt-1">Auction bid, purchase price, or salvage value paid</p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Towing / Transport Cost</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm cost-sym">₦</span>
            <input type="number" name="towing_cost" id="c_towing" min="0" step="any" value="{{ old('towing_cost', 0) }}" oninput="recalcTotal()"
              class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400" placeholder="0">
          </div>
          <p class="text-[10px] text-gray-400 mt-1">Inbound towing, shipping, or transport fees</p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Processing / Labour Cost</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm cost-sym">₦</span>
            <input type="number" name="processing_cost" id="c_processing" min="0" step="any" value="{{ old('processing_cost', 0) }}" oninput="recalcTotal()"
              class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400" placeholder="0">
          </div>
          <p class="text-[10px] text-gray-400 mt-1">Dismantling labour, cleaning, tagging</p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Other Costs</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm cost-sym">₦</span>
            <input type="number" name="other_cost" id="c_other" min="0" step="any" value="{{ old('other_cost', 0) }}" oninput="recalcTotal()"
              class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400" placeholder="0">
          </div>
          <p class="text-[10px] text-gray-400 mt-1">Storage, fees, compliance, anything else</p>
        </div>

        {{-- Live total --}}
        <div class="sm:col-span-2">
          <div class="bg-navy rounded-xl px-5 py-4 flex items-center justify-between">
            <div>
              <div class="text-xs text-white/60 font-body uppercase tracking-wider">Total Acquisition Cost</div>
              <div class="text-[10px] text-white/30 mt-0.5">Salvage + Towing + Processing + Other</div>
            </div>
            <div id="totalCostDisplay" class="font-display font-700 text-2xl text-gold">₦0</div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Break-Even Target (Days)</label>
          <input type="number" name="break_even_days" min="1" max="3650" value="{{ old('break_even_days', 90) }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400" placeholder="90">
          <p class="text-[10px] text-gray-400 mt-1">Target days to recover total cost from parts sales. Default: 90 days</p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Vehicle Purpose</label>
          <select name="vehicle_status" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="Parts"     {{ old('vehicle_status','Parts') === 'Parts'     ? 'selected' : '' }}>Parts — Dismantle for parts</option>
            <option value="Rebuilder" {{ old('vehicle_status')         === 'Rebuilder' ? 'selected' : '' }}>Rebuilder — Resell as running vehicle</option>
          </select>
          <p class="text-[10px] text-gray-400 mt-1">Affects reporting and post-harvest workflow</p>
        </div>

        @php
          $damageCodes = ['FE'=>'Front End','RE'=>'Rear End','TP'=>'Top / Roof','RS'=>'Rollover','WS'=>'Water / Flood','VD'=>'Vandalism','MH'=>'Mechanical / Hail','BP'=>'Burn (Passenger)','BD'=>'Burn (Driver)','OT'=>'Other'];
        @endphp
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Primary Damage Code <span class="font-normal normal-case text-gray-300">(optional)</span></label>
          <select name="primary_damage_code" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">None / Unknown</option>
            @foreach($damageCodes as $code => $label)
            <option value="{{ $code }}" {{ old('primary_damage_code') === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Secondary Damage Code <span class="font-normal normal-case text-gray-300">(optional)</span></label>
          <select name="secondary_damage_code" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">None / Unknown</option>
            @foreach($damageCodes as $code => $label)
            <option value="{{ $code }}" {{ old('secondary_damage_code') === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
            @endforeach
          </select>
        </div>

      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        Register Vehicle &amp; Start Harvesting Checklist
      </button>
      <a href="{{ route('admin.dashboard') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const LOCATION_CURRENCIES = {
    'Waxahachie TX': { sym: '$',   code: 'USD' },
    'Kennedale TX':  { sym: '$',   code: 'USD' },
    'Elkhorn WI':    { sym: '$',   code: 'USD' },
    'Accra Ghana':   { sym: 'GH₵', code: 'GHS' },
};

function updateCostSymbols() {
    const loc = document.getElementById('f_location')?.value || '';
    const cur = LOCATION_CURRENCIES[loc] || { sym: '₦', code: 'NGN' };
    document.querySelectorAll('.cost-sym').forEach(el => el.textContent = cur.sym);
    recalcTotal();
}
document.getElementById('f_location')?.addEventListener('change', updateCostSymbols);
updateCostSymbols();

function recalcTotal() {
    const s = parseFloat(document.getElementById('c_salvage')?.value    || 0);
    const t = parseFloat(document.getElementById('c_towing')?.value     || 0);
    const p = parseFloat(document.getElementById('c_processing')?.value || 0);
    const o = parseFloat(document.getElementById('c_other')?.value      || 0);
    const total = s + t + p + o;
    const loc = document.getElementById('f_location')?.value || '';
    const cur = LOCATION_CURRENCIES[loc] || { sym: '₦', code: 'NGN' };
    const formatted = cur.code === 'NGN'
        ? cur.sym + Math.round(total).toLocaleString()
        : cur.sym + total.toFixed(2);
    document.getElementById('totalCostDisplay').textContent = formatted;
}

const VEHICLE_MODELS = {
    @foreach(\App\Data\VehicleDatabase::makes() as $mk)
        "{{ $mk }}": {!! json_encode(\App\Data\VehicleDatabase::modelsForMake($mk)) !!},
    @endforeach
};

const makeSelect       = document.getElementById('f_make');
const modelSelect      = document.getElementById('f_model');
const modelCustomInput = document.getElementById('f_model_custom');

makeSelect.addEventListener('change', function() {
    const models = VEHICLE_MODELS[this.value] || [];
    if (this.value === 'UNIVERSAL' || models.length === 0) {
        modelSelect.classList.add('hidden'); modelSelect.required = false;
        modelCustomInput.classList.remove('hidden'); modelCustomInput.name = 'model'; modelCustomInput.required = true;
        modelSelect.name = ''; return;
    }
    modelSelect.classList.remove('hidden'); modelSelect.required = true; modelSelect.name = 'model';
    modelCustomInput.classList.add('hidden'); modelCustomInput.required = false; modelCustomInput.name = '';
    modelSelect.innerHTML = '<option value="">Select Model</option>' +
        models.map(m => `<option value="${m}">${m}</option>`).join('') +
        '<option value="__other__">OTHER / NOT LISTED</option>';
    loadEngineOptions();
});

modelSelect.addEventListener('change', function() {
    if (this.value === '__other__') {
        modelSelect.classList.add('hidden'); modelSelect.name = '';
        modelCustomInput.classList.remove('hidden'); modelCustomInput.name = 'model';
        modelCustomInput.required = true; modelCustomInput.focus();
    }
    loadEngineOptions();
});

document.getElementById('f_year').addEventListener('change', loadEngineOptions);
modelCustomInput.addEventListener('change', loadEngineOptions);

let engineOptionsTimer = null;
function loadEngineOptions() { clearTimeout(engineOptionsTimer); engineOptionsTimer = setTimeout(fetchEngineOptions, 300); }

async function fetchEngineOptions() {
    const make  = makeSelect.value;
    const model = modelSelect.classList.contains('hidden') ? modelCustomInput.value : modelSelect.value;
    const year  = document.getElementById('f_year').value;
    const engineSelect = document.getElementById('f_engineSelect');
    const engineCustom = document.getElementById('f_engine');
    if (!make || make === 'UNIVERSAL' || !model || model === '__other__' || !year) {
        engineSelect.innerHTML = '<option value="">Select Make/Model/Year first</option>'; return;
    }
    engineSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/harvest/engine-options?make=${encodeURIComponent(make)}&model=${encodeURIComponent(model)}&year=${encodeURIComponent(year)}`);
        const data = await res.json();
        if (!data.options || data.options.length === 0) {
            engineSelect.innerHTML = '<option value="">No engine data — type manually below</option>';
            engineCustom.classList.remove('hidden'); return;
        }
        engineSelect.innerHTML = '<option value="">Select Engine</option>' +
            data.options.map(o => `<option value="${o.label}">${o.label}</option>`).join('') +
            '<option value="__other__">OTHER / NOT LISTED</option>';
        engineCustom.classList.add('hidden');
    } catch(e) {
        engineSelect.innerHTML = '<option value="">Could not load — type manually below</option>';
        engineCustom.classList.remove('hidden');
    }
}

document.getElementById('f_engineSelect').addEventListener('change', function() {
    const engineCustom = document.getElementById('f_engine');
    if (this.value === '__other__') { engineCustom.classList.remove('hidden'); engineCustom.value = ''; engineCustom.focus(); }
    else { engineCustom.classList.add('hidden'); engineCustom.value = this.value; }
});

const tabVin = document.getElementById('tabVin'), tabManual = document.getElementById('tabManual'), tabSearch = document.getElementById('tabSearch');
const panelVin = document.getElementById('panelVin'), panelSearch = document.getElementById('panelSearch');
const ACTIVE   = 'harvest-tab-btn bg-navy text-white font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide';
const INACTIVE = 'harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors';
function setTab(active) {
    tabVin.className = active==='vin' ? ACTIVE : INACTIVE; tabManual.className = active==='manual' ? ACTIVE : INACTIVE; tabSearch.className = active==='search' ? ACTIVE : INACTIVE;
    panelVin.classList.toggle('hidden', active !== 'vin'); panelSearch.classList.toggle('hidden', active !== 'search');
}
tabVin.addEventListener('click', () => setTab('vin')); tabManual.addEventListener('click', () => setTab('manual')); tabSearch.addEventListener('click', () => setTab('search'));

async function searchDonors() {
    const q = document.getElementById('donorSearchInput').value.trim();
    const resultsBox = document.getElementById('donorSearchResults');
    if (!q) return;
    resultsBox.classList.remove('hidden'); resultsBox.innerHTML = '<div class="text-xs text-gray-400">Searching...</div>';
    try {
        const res = await fetch(`/admin/harvest/search-donors?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.results || data.results.length === 0) {
            resultsBox.innerHTML = '<div class="text-xs text-gray-400">No matching donor vehicles found.</div>'; return;
        }
        resultsBox.innerHTML = data.results.map(d => `
            <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2.5">
                <div><div class="font-body font-500 text-sm text-navy">${d.year} ${d.make} ${d.model}${d.trim?' · '+d.trim:''}</div>
                <div class="text-xs text-gray-400 font-mono mt-0.5">${d.vin||'No VIN'} · ${d.location||''}</div></div>
                ${d.status==='in_progress' ? `<a href="/admin/harvest/${d.session_id}/checklist" class="text-xs bg-gold text-navy px-3 py-1.5 rounded-lg font-500">Continue Checklist</a>` : '<span class="text-xs text-gray-400">Completed</span>'}
            </div>`).join('');
    } catch(e) { resultsBox.innerHTML = '<div class="text-xs text-red-600">Search failed.</div>'; }
}
document.getElementById('donorSearchInput').addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();searchDonors();} });

async function decodeVin() {
    const vin = document.getElementById('vinInput').value.trim().toUpperCase();
    if (vin.length !== 17) { document.getElementById('vinStatus').textContent='VIN must be 17 characters'; document.getElementById('vinStatus').style.color='#A32D2D'; return; }
    const btn = document.getElementById('decodeBtn');
    btn.disabled = true; btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Decoding...';
    try {
        const res = await fetch('/admin/harvest/vin-decode', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({vin}) });
        const data = await res.json();
        if (!res.ok || data.error) { document.getElementById('vinStatus').textContent=data.error||'Decode failed'; document.getElementById('vinStatus').style.color='#A32D2D'; return; }
        const v = data.vehicle;
        document.getElementById('vinField').value = vin;
        document.getElementById('f_make').value = (v.make||'').toUpperCase(); document.getElementById('f_make').dispatchEvent(new Event('change'));
        setTimeout(()=>{ document.getElementById('f_model').value=(v.model||'').toUpperCase(); }, 0);
        document.getElementById('f_year').value=v.year||''; document.getElementById('f_trim').value=v.trim||''; document.getElementById('f_body').value=v.body_style||'';
        if(v.engine_l){ document.getElementById('f_engine').value=v.engine_l+'L'; document.getElementById('f_engine').classList.remove('hidden'); document.getElementById('f_engineSelect').innerHTML='<option value="">(filled from VIN)</option>'; }
        const oem = v.oem_suggestion||{};
        document.getElementById('decodedLabel').textContent=`${v.year} ${v.make} ${v.model}${v.trim?' · '+v.trim:''}`;
        document.getElementById('decodedSub').textContent=[v.engine_l?v.engine_l+'L':'',oem.engine_code||'',v.engine_cyl?v.engine_cyl+'-Cyl':'',v.drive_type||'',oem.transmission_code||'',oem.pin_count?oem.pin_count+'-pin':'',v.origin?'Built in '+v.origin:''].filter(Boolean).join(' · ');
        document.getElementById('decodedBanner').classList.remove('hidden');
        document.getElementById('vinStatus').textContent='✓ Vehicle identified'; document.getElementById('vinStatus').style.color='#27500A';
    } catch(e) { document.getElementById('vinStatus').textContent='Network error. Fill in manually.'; document.getElementById('vinStatus').style.color='#854F0B'; }
    finally { btn.disabled=false; btn.innerHTML='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Decode VIN'; }
}
document.getElementById('vinInput').addEventListener('keydown', e=>{ if(e.key==='Enter'){e.preventDefault();decodeVin();} });
document.getElementById('donorForm').addEventListener('submit', function() {
    if (!document.getElementById('vinField').value) document.getElementById('vinField').value=document.getElementById('vinInput').value.trim().toUpperCase();
});
</script>
@endpush
