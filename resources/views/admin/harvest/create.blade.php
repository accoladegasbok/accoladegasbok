{{-- FILE: resources/views/admin/harvest/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Register Donor Vehicle')
@section('page-title', 'New Harvest')
@section('page-sub', 'Register a donor vehicle and start the parts harvesting checklist')

@section('content')
<div class="max-w-3xl">

  {{-- ── Tab switcher ──────────────────────────────────────────────── --}}
  <div class="flex gap-2 mb-5">
    <button type="button" id="tabVin"
      class="harvest-tab-btn bg-navy text-white font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide">
      VIN DECODE
    </button>
    <button type="button" id="tabManual"
      class="harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors">
      MANUAL ENTRY
    </button>
    <button type="button" id="tabSearch"
      class="harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors">
      SEARCH EXISTING
    </button>
  </div>

  {{-- ── Tab 1: VIN decode box ────────────────────────────────────── --}}
  <div id="panelVin" class="harvest-tab-panel stat-card mb-6">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Decode the VIN</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Enter the 17-character VIN from the dashboard, door jamb, or title. The system will fill in the vehicle details automatically.</p>

    <div class="flex gap-2">
      <input type="text" id="vinInput" maxlength="17" placeholder="17-character VIN (e.g. 1HGBH41JXMN109186)"
        class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm font-body font-mono uppercase tracking-widest focus:outline-none focus:border-gold focus:ring-1 focus:ring-yellow-400 placeholder:normal-case placeholder:tracking-normal"
        oninput="document.getElementById('vinCount').textContent=this.value.length+'/17'">
      <button onclick="decodeVin()" id="decodeBtn"
        class="bg-navy text-white font-display font-700 text-sm px-5 py-3 rounded-xl tracking-wide hover:bg-navy-light transition-colors flex items-center gap-2 whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Decode VIN
      </button>
    </div>
    <div class="flex justify-between mt-1.5">
      <span id="vinCount" class="text-xs text-gray-400 font-mono">0/17</span>
      <span id="vinStatus" class="text-xs font-body"></span>
    </div>
  </div>

  {{-- ── Tab 3: Search Existing donor vehicles ───────────────────────
       NOTE: requires GET route admin.harvest.search-donors returning
       { results: [{ year, make, model, trim, vin, location, status, session_id }] } ── --}}
  <div id="panelSearch" class="harvest-tab-panel stat-card mb-6 hidden">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Search Existing Donor Vehicles</h2>
    <p class="text-xs text-gray-400 font-body mb-4">Find a donor vehicle already registered in the system by make, model, VIN, or notes.</p>

    <div class="flex gap-2">
      <input type="text" id="donorSearchInput" placeholder="Search by make, model, plate number, or notes..."
        class="flex-1 border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-yellow-400">
      <button onclick="searchDonors()" id="donorSearchBtn"
        class="border border-navy text-navy font-display font-700 text-sm px-5 py-3 rounded-xl tracking-wide hover:bg-navy hover:text-white transition-colors flex items-center gap-2 whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        Search
      </button>
    </div>
    <div id="donorSearchResults" class="mt-3 space-y-2 hidden"></div>
  </div>

  {{-- Vehicle registration form --}}
  <form method="POST" action="{{ route('admin.harvest.store') }}" id="donorForm">
    @csrf

    {{-- Decoded vehicle banner --}}
    <div id="decodedBanner" class="hidden mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
      <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <div>
        <div id="decodedLabel" class="text-green-800 font-body font-500 text-sm"></div>
        <div id="decodedSub" class="text-green-600 text-xs font-body mt-0.5"></div>
      </div>
    </div>

    <div class="stat-card mb-6">
      <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Step 2 — Vehicle Details</h2>

      <input type="hidden" name="vin" id="vinField">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Make *</label>
          <select name="make" id="f_make" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Make</option>
            @foreach(\App\Data\VehicleDatabase::makes() as $mk)
              <option value="{{ $mk }}" {{ old('make')===$mk?'selected':'' }}>{{ $mk }}</option>
            @endforeach
            <option value="UNIVERSAL" {{ old('make')==='UNIVERSAL'?'selected':'' }}>OTHER / NOT LISTED</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
          <select name="model" id="f_model" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Model</option>
            @if(old('model'))
              <option value="{{ old('model') }}" selected>{{ old('model') }}</option>
            @endif
          </select>
          <input type="text" id="f_model_custom" placeholder="Type model if not listed"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 mt-1.5 hidden">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year *</label>
          <select name="year" id="f_year" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Year</option>
            @foreach(\App\Data\VehicleDatabase::years() as $yr)
              <option value="{{ $yr }}" {{ (string) old('year') === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Trim</label>
          <input type="text" name="trim" id="f_trim" value="{{ old('trim') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="LE, Sport, EX-L...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Body Style</label>
          <input type="text" name="body_style" id="f_body" value="{{ old('body_style') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="Sedan, SUV, Coupe...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Engine</label>
          <select id="f_engineSelect"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Select Make/Model/Year first</option>
          </select>
          <input type="text" name="engine" id="f_engine" value="{{ old('engine') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 mt-1.5 hidden"
            placeholder="e.g. 2.5L I4 (type manually if not listed)">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Exterior Colour</label>
          <input type="text" name="colour" value="{{ old('colour') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="White, Silver, Black...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage</label>
          <input type="number" name="mileage" value="{{ old('mileage') }}" min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="65000 (optional)">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Vehicle Condition *</label>
          <select name="condition" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
            <option value="Good" {{ old('condition','Good')==='Good'?'selected':'' }}>Good</option>
            <option value="Fair" {{ old('condition')==='Fair'?'selected':'' }}>Fair</option>
            <option value="Poor" {{ old('condition')==='Poor'?'selected':'' }}>Poor</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Source *</label>
          <select name="source" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
            @foreach(['Auction','Insurance','Private Sale','Dealer','Other'] as $s)
            <option value="{{ $s }}" {{ old('source')===$s?'selected':'' }}>{{ $s }}</option>
            @endforeach
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Storage Location *</label>
          <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
            @foreach($locations as $loc)
            <option value="{{ $loc }}"
              {{ old('location', $staffLocation && $staffLocation !== 'All' ? $staffLocation : '') === $loc ? 'selected' : '' }}>
              {{ $loc }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Notes</label>
          <textarea name="notes" rows="2" placeholder="Any special notes about this vehicle..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit"
        class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        Register Vehicle & Start Harvesting Checklist
      </button>
      <a href="{{ route('admin.dashboard') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>

  </form>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Model dropdown auto-populate (mirrors customer search page) ───────────
const VEHICLE_MODELS = {
    @foreach(\App\Data\VehicleDatabase::makes() as $mk)
        "{{ $mk }}": {!! json_encode(\App\Data\VehicleDatabase::modelsForMake($mk)) !!},
    @endforeach
};

const makeSelect      = document.getElementById('f_make');
const modelSelect      = document.getElementById('f_model');
const modelCustomInput = document.getElementById('f_model_custom');

makeSelect.addEventListener('change', function() {
    const models = VEHICLE_MODELS[this.value] || [];
    if (this.value === 'UNIVERSAL' || models.length === 0) {
        modelSelect.classList.add('hidden');
        modelSelect.required = false;
        modelCustomInput.classList.remove('hidden');
        modelCustomInput.name = 'model';
        modelCustomInput.required = true;
        modelSelect.name = '';
        return;
    }
    modelSelect.classList.remove('hidden');
    modelSelect.required = true;
    modelSelect.name = 'model';
    modelCustomInput.classList.add('hidden');
    modelCustomInput.required = false;
    modelCustomInput.name = '';
    modelSelect.innerHTML = '<option value="">Select Model</option>' +
        models.map(m => `<option value="${m}">${m}</option>`).join('') +
        '<option value="__other__">OTHER / NOT LISTED</option>';
    loadEngineOptions();
});

modelSelect.addEventListener('change', function() {
    if (this.value === '__other__') {
        modelSelect.classList.add('hidden');
        modelSelect.name = '';
        modelCustomInput.classList.remove('hidden');
        modelCustomInput.name = 'model';
        modelCustomInput.required = true;
        modelCustomInput.focus();
    }
    loadEngineOptions();
});

document.getElementById('f_year').addEventListener('change', loadEngineOptions);
modelCustomInput.addEventListener('change', loadEngineOptions);

// ── Engine options dropdown — populated from OemDatabase based on
// Make/Model/Year, same data a VIN decode would reveal for one car,
// here offered as a choice since we don't have a VIN to read it from.
let engineOptionsTimer = null;
function loadEngineOptions() {
    clearTimeout(engineOptionsTimer);
    engineOptionsTimer = setTimeout(fetchEngineOptions, 300);
}

async function fetchEngineOptions() {
    const make  = makeSelect.value;
    const model = modelSelect.classList.contains('hidden') ? modelCustomInput.value : modelSelect.value;
    const year  = document.getElementById('f_year').value;
    const engineSelect = document.getElementById('f_engineSelect');
    const engineCustom  = document.getElementById('f_engine');

    if (!make || make === 'UNIVERSAL' || !model || model === '__other__' || !year) {
        engineSelect.innerHTML = '<option value="">Select Make/Model/Year first</option>';
        return;
    }

    engineSelect.innerHTML = '<option value="">Loading...</option>';

    try {
        const res  = await fetch(`/admin/harvest/engine-options?make=${encodeURIComponent(make)}&model=${encodeURIComponent(model)}&year=${encodeURIComponent(year)}`);
        const data = await res.json();

        if (!data.options || data.options.length === 0) {
            engineSelect.innerHTML = '<option value="">No engine data on file \u2014 type manually below</option>';
            engineCustom.classList.remove('hidden');
            return;
        }

        engineSelect.innerHTML = '<option value="">Select Engine</option>' +
            data.options.map(o => `<option value="${o.label}">${o.label}</option>`).join('') +
            '<option value="__other__">OTHER / NOT LISTED</option>';
        engineCustom.classList.add('hidden');
    } catch (e) {
        engineSelect.innerHTML = '<option value="">Could not load \u2014 type manually below</option>';
        engineCustom.classList.remove('hidden');
    }
}

document.getElementById('f_engineSelect').addEventListener('change', function() {
    const engineCustom = document.getElementById('f_engine');
    if (this.value === '__other__') {
        engineCustom.classList.remove('hidden');
        engineCustom.value = '';
        engineCustom.focus();
    } else {
        engineCustom.classList.add('hidden');
        engineCustom.value = this.value;
    }
});

// ── Tab switching ───────────────────────────────────────────────────────────
const tabVin     = document.getElementById('tabVin');
const tabManual  = document.getElementById('tabManual');
const tabSearch  = document.getElementById('tabSearch');
const panelVin    = document.getElementById('panelVin');
const panelSearch = document.getElementById('panelSearch');

const ACTIVE   = 'harvest-tab-btn bg-navy text-white font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide';
const INACTIVE = 'harvest-tab-btn bg-gray-100 text-gray-500 hover:bg-gray-200 font-display font-700 text-xs px-5 py-2.5 rounded-full tracking-wide transition-colors';

function setTab(active) {
    tabVin.className    = active === 'vin'    ? ACTIVE : INACTIVE;
    tabManual.className = active === 'manual' ? ACTIVE : INACTIVE;
    tabSearch.className = active === 'search' ? ACTIVE : INACTIVE;

    panelVin.classList.toggle('hidden', active !== 'vin');
    panelSearch.classList.toggle('hidden', active !== 'search');
    // "Manual" just hides the VIN + Search panels and lets Step 2 stand alone.
}

tabVin.addEventListener('click', () => setTab('vin'));
tabManual.addEventListener('click', () => setTab('manual'));
tabSearch.addEventListener('click', () => setTab('search'));

// ── Non-VIN donor vehicle search ───────────────────────────────────────────
async function searchDonors() {
    const q = document.getElementById('donorSearchInput').value.trim();
    const resultsBox = document.getElementById('donorSearchResults');
    if (!q) return;

    resultsBox.classList.remove('hidden');
    resultsBox.innerHTML = '<div class="text-xs text-gray-400 font-body">Searching...</div>';

    try {
        const res = await fetch(`/admin/harvest/search-donors?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        if (!data.results || data.results.length === 0) {
            resultsBox.innerHTML = '<div class="text-xs text-gray-400 font-body">No matching donor vehicles found. Switch to Manual Entry to register this vehicle.</div>';
            return;
        }

        resultsBox.innerHTML = data.results.map(d => `
            <div class="flex items-center justify-between border border-gray-200 rounded-lg px-3 py-2.5">
                <div>
                    <div class="font-body font-500 text-sm text-navy">${d.year} ${d.make} ${d.model}${d.trim ? ' · ' + d.trim : ''}</div>
                    <div class="text-xs text-gray-400 font-mono mt-0.5">${d.vin || 'No VIN on file'} · ${d.location || ''}</div>
                </div>
                ${d.status === 'in_progress'
                    ? `<a href="/admin/harvest/${d.session_id}/checklist" class="text-xs font-body bg-gold text-navy px-3 py-1.5 rounded-lg hover:bg-yellow-500 transition-colors font-500">Continue Checklist</a>`
                    : `<span class="text-xs font-body text-gray-400">Already completed</span>`
                }
            </div>
        `).join('');
    } catch (e) {
        resultsBox.innerHTML = '<div class="text-xs text-red-600 font-body">Search failed. Switch to Manual Entry to register this vehicle.</div>';
    }
}

document.getElementById('donorSearchInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchDonors(); }
});

async function decodeVin() {
  const vin = document.getElementById('vinInput').value.trim().toUpperCase();
  if (vin.length !== 17) {
    document.getElementById('vinStatus').textContent = 'VIN must be exactly 17 characters';
    document.getElementById('vinStatus').style.color = '#A32D2D';
    return;
  }

  const btn = document.getElementById('decodeBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Decoding...';

  try {
    const res  = await fetch('/admin/harvest/vin-decode', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ vin }),
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      document.getElementById('vinStatus').textContent = data.error || 'Decode failed';
      document.getElementById('vinStatus').style.color = '#A32D2D';
      return;
    }

    const v = data.vehicle;
    document.getElementById('vinField').value   = vin;
    document.getElementById('f_make').value     = (v.make || '').toUpperCase();
    document.getElementById('f_make').dispatchEvent(new Event('change'));
    setTimeout(() => {
        document.getElementById('f_model').value = (v.model || '').toUpperCase();
    }, 0);
    document.getElementById('f_year').value     = v.year   || '';
    document.getElementById('f_trim').value     = v.trim   || '';
    document.getElementById('f_body').value     = v.body_style || '';
    document.getElementById('f_engine').value = v.engine_l ? v.engine_l + 'L' : '';
    if (v.engine_l) {
        document.getElementById('f_engine').classList.remove('hidden');
        document.getElementById('f_engineSelect').innerHTML = '<option value="">(filled from VIN decode below)</option>';
    }
    const label = document.getElementById('decodedLabel');
    const sub   = document.getElementById('decodedSub');
    label.textContent = `${v.year} ${v.make} ${v.model}${v.trim ? ' · ' + v.trim : ''}`;

    // Build detail line with OEM codes
    const oem = v.oem_suggestion || {};
    sub.textContent = [
        v.engine_l    ? v.engine_l + 'L'          : '',
        oem.engine_code                            || '',
        v.engine_cyl  ? v.engine_cyl + '-Cyl'     : '',
        v.drive_type                               || '',
        v.body_style                               || '',
        oem.transmission_code                      || '',
        oem.pin_count ? oem.pin_count + '-pin'     : '',
        v.origin      ? 'Built in ' + v.origin     : '',
    ].filter(Boolean).join(' · ');

    // Pre-fill OEM fields if they exist on the page
    const oemEngField  = document.getElementById('f_oem_engine');
    const oemTransField= document.getElementById('f_oem_trans');
    const oemPinField  = document.getElementById('f_pin_count');
    if (oemEngField  && oem.engine_code)       oemEngField.value  = oem.engine_code;
    if (oemTransField && oem.transmission_code) oemTransField.value = oem.transmission_code;
    if (oemPinField  && oem.pin_count)          oemPinField.value  = oem.pin_count;
    document.getElementById('decodedBanner').classList.remove('hidden');

    document.getElementById('vinStatus').textContent = '✓ Vehicle identified';
    document.getElementById('vinStatus').style.color = '#27500A';

  } catch (e) {
    document.getElementById('vinStatus').textContent = 'Network error. Fill in manually below.';
    document.getElementById('vinStatus').style.color = '#854F0B';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> Decode VIN';
  }
}

document.getElementById('vinInput').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); decodeVin(); } });

document.getElementById('donorForm').addEventListener('submit', function() {
  if (!document.getElementById('vinField').value) {
    document.getElementById('vinField').value = document.getElementById('vinInput').value.trim().toUpperCase();
  }
});
</script>
@endpush
