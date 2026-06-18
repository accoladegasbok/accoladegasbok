{{-- FILE: resources/views/admin/harvest/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Register Donor Vehicle')
@section('page-title', 'New Harvest')
@section('page-sub', 'Register a donor vehicle and start the parts harvesting checklist')

@section('content')
<div class="max-w-3xl">

  {{-- VIN decode box --}}
  <div class="stat-card mb-6">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Step 1 — Decode the VIN</h2>
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
          <input type="text" name="make" id="f_make" value="{{ old('make') }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="Toyota, Honda, Nissan...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
          <input type="text" name="model" id="f_model" value="{{ old('model') }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="Camry, Accord, Altima...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year *</label>
          <input type="number" name="year" id="f_year" value="{{ old('year') }}" required min="1990" max="{{ date('Y')+1 }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="2019">
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
          <input type="text" name="engine" id="f_engine" value="{{ old('engine') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="2.5L I4, 3.5L V6...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Exterior Colour</label>
          <input type="text" name="colour" value="{{ old('colour') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="White, Silver, Black...">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage *</label>
          <input type="number" name="mileage" value="{{ old('mileage') }}" required min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="65000">
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
    document.getElementById('f_make').value     = v.make   || '';
    document.getElementById('f_model').value    = v.model  || '';
    document.getElementById('f_year').value     = v.year   || '';
    document.getElementById('f_trim').value     = v.trim   || '';
    document.getElementById('f_body').value     = v.body_style || '';
    document.getElementById('f_engine').value = v.engine_l ? v.engine_l + 'L' : '';
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
