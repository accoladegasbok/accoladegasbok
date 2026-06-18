@extends('layouts.app')
@section('title', 'Compatibility Checker — Auto Zenith Parts')
@section('meta_desc', 'Enter your vehicle Year, Make and Model — or decode your VIN — to see all compatible parts in stock, including interchangeable alternatives and exact coverage years.')
@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════
     HERO — Compatibility Search Form
═══════════════════════════════════════════════════════════════════════ --}}
<div class="bg-navy relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:linear-gradient(rgba(255,255,255,.15) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.15) 1px,transparent 1px);background-size:32px 32px;"></div>
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 py-10">
        <div class="text-center mb-7">
            <h1 class="font-display font-800 text-white text-4xl sm:text-5xl tracking-wide leading-tight">
                WILL IT <span class="text-gold">FIT</span>?
            </h1>
            <p class="text-gray-300 font-body text-sm mt-2">Enter your vehicle to see compatible parts — including interchangeable alternatives and exact coverage years</p>
        </div>

        {{-- ── VIN Decode Box ──────────────────────────────────────────── --}}
        <div class="max-w-3xl mx-auto mb-4">
            <div class="bg-white rounded-xl p-1 flex gap-1 shadow-2xl">
                <div class="flex-1 relative">
                    <input type="text" id="vinInput" maxlength="17" placeholder="Enter 17-digit VIN to auto-fill vehicle (optional)"
                        class="vin-input w-full px-4 py-3 rounded-lg text-sm font-body font-500 border border-gray-200 focus:outline-none uppercase tracking-wider placeholder:normal-case placeholder:tracking-normal placeholder:text-gray-400 placeholder:font-300">
                    <div id="vinCharCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-mono">0/17</div>
                </div>
                <button id="vinDecodeBtn" type="button" class="bg-gold hover:bg-gold-dark text-navy font-display font-700 text-sm px-5 py-3 rounded-lg transition-colors tracking-wide whitespace-nowrap flex items-center gap-2">
                    DECODE VIN
                </button>
            </div>
            <p id="vinStatus" class="text-center text-gray-400 text-xs mt-2 font-body"></p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-2xl max-w-3xl mx-auto">
            <form method="GET" action="{{ route('parts.compatibility') }}" id="compatForm">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">

                    {{-- Make --}}
                    <div>
                        <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Make *</label>
                        <select name="make" id="makeSelect" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                            <option value="">Select Make</option>
                            @foreach($makes as $brand)
                                <option value="{{ $brand }}" {{ $make === $brand ? 'selected' : '' }}>{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Model (populated via AJAX) --}}
                    <div>
                        <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Model *</label>
                        <select name="model" id="modelSelect" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                            <option value="">Select Model</option>
                            @if(!empty($model))
                            <option value="{{ $model }}" selected>{{ $model }}</option>
                            @endif
                        </select>
                    </div>

                    {{-- Year --}}
                    <div>
                        <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Year *</label>
                        <select name="year" id="yearSelect" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                            <option value="">Select Year</option>
                            @for($y = date('Y'); $y >= 1995; $y--)
                                <option value="{{ $y }}" {{ (int)$year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 mb-3">
                    <p class="text-xs text-gray-400 mb-2">Looking for a specific part? Narrow your search (optional):</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        {{-- Part Category --}}
                        <div>
                            <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Part Category</label>
                            <select name="category" id="categorySelect"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Part Name (free text) --}}
                        <div>
                            <label class="block text-xs font-body font-500 text-gray-500 mb-1 uppercase tracking-wider">Part Name</label>
                            <input type="text" name="part_name" id="partNameInput" value="{{ $partName }}"
                                placeholder="e.g. Alternator, Headlight, Transmission..."
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold bg-white">
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gold hover:bg-gold-dark text-navy font-display font-700 text-sm px-6 py-3.5 rounded-lg transition-colors tracking-wide">
                    CHECK COMPATIBILITY
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     RESULTS
═══════════════════════════════════════════════════════════════════════ --}}
@if($result)
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Vehicle summary banner --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 flex flex-wrap items-center gap-4">
        <div>
            <span class="text-xs text-gray-400 uppercase tracking-wider">Your Vehicle</span>
            <div class="font-display font-700 text-navy text-lg">{{ $result['vehicle']['year'] }} {{ $result['vehicle']['make'] }} {{ $result['vehicle']['model'] }}</div>
        </div>
        @if($result['part_name'] || $result['category'])
        <div>
            <span class="text-xs text-gray-400 uppercase tracking-wider">Searching For</span>
            <div class="font-display font-700 text-navy text-sm">{{ $result['part_name'] ?: 'Any part' }} {{ $result['category'] ? '· ' . $result['category'] : '' }}</div>
        </div>
        @endif
        @if($result['vehicle']['oem']['engine_code'])
        <div>
            <span class="text-xs text-gray-400 uppercase tracking-wider">Engine</span>
            <div class="font-mono font-700 text-sm text-gray-700">{{ $result['vehicle']['oem']['engine_code'] }}</div>
        </div>
        @endif
        @if($result['vehicle']['oem']['transmission_code'])
        <div>
            <span class="text-xs text-gray-400 uppercase tracking-wider">Transmission</span>
            <div class="font-mono font-700 text-sm text-gray-700">
                {{ $result['vehicle']['oem']['transmission_code'] }}
                @if($result['vehicle']['oem']['pin_count'])({{ $result['vehicle']['oem']['pin_count'] }}-pin)@endif
            </div>
        </div>
        @endif
        <div class="ml-auto">
            <span class="inline-flex items-center gap-1.5 bg-{{ $result['total_found'] > 0 ? 'green' : 'gray' }}-100 text-{{ $result['total_found'] > 0 ? 'green' : 'gray' }}-700 text-xs font-700 px-3 py-1.5 rounded-full">
                {{ $result['total_found'] }} part{{ $result['total_found'] === 1 ? '' : 's' }} found
            </span>
        </div>
    </div>

    {{-- Direct fit parts --}}
    @if(count($result['direct_parts']) > 0)
    <div class="mb-8">
        <h2 class="font-display font-700 text-navy text-lg mb-3 flex items-center gap-2">
            <span class="bg-green-100 text-green-700 text-xs px-2.5 py-1 rounded-full">DIRECT FIT</span>
            In stock for your exact vehicle
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($result['direct_parts'] as $part)
            <a href="{{ route('parts.show', $part['id']) }}" class="bg-white border border-gray-200 rounded-xl p-4 hover:border-gold hover:shadow-md transition-all block">
                <div class="font-700 text-navy text-sm mb-1">{{ $part['part_name'] }}</div>
                <div class="text-xs text-gray-400 mb-2">{{ $part['brand'] }} {{ $part['model'] }} {{ $part['year_from'] }}–{{ $part['year_to'] }} · Grade {{ $part['condition_grade'] }}</div>
                <div class="flex items-center justify-between">
                    <span class="font-display font-700 text-navy">{{ $part['price_display'] }}</span>
                    <span class="text-xs text-gray-400">📍 {{ $part['location'] }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Interchange parts --}}
    @if(count($result['interchange_parts']) > 0)
    <div class="mb-8">
        <h2 class="font-display font-700 text-navy text-lg mb-3 flex items-center gap-2">
            <span class="bg-blue-100 text-blue-700 text-xs px-2.5 py-1 rounded-full">ALSO FITS</span>
            Interchangeable parts from other models with the same engine/transmission
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($result['interchange_parts'] as $part)
            <a href="{{ route('parts.show', $part['id']) }}" class="bg-white border border-gray-200 rounded-xl p-4 hover:border-gold hover:shadow-md transition-all block">
                <div class="font-700 text-navy text-sm mb-1">{{ $part['part_name'] }}</div>
                <div class="text-xs text-gray-400 mb-2">{{ $part['brand'] }} {{ $part['model'] }} {{ $part['year_from'] }}–{{ $part['year_to'] }} · Grade {{ $part['condition_grade'] }}</div>
                <div class="flex items-center justify-between">
                    <span class="font-display font-700 text-navy">{{ $part['price_display'] }}</span>
                    <span class="text-xs text-gray-400">📍 {{ $part['location'] }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- No parts found --}}
    @if($result['total_found'] === 0)
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center mb-8">
        <p class="text-gray-500 font-body text-sm mb-3">
            No {{ $result['part_name'] ?: 'parts' }} currently in stock for this vehicle.
        </p>
        <a href="https://wa.me/15125873425?text={{ urlencode('Hi! Do you have ' . ($result['part_name'] ?: 'parts') . ' for a ' . $result['vehicle']['year'] . ' ' . $result['vehicle']['make'] . ' ' . $result['vehicle']['model'] . '?') }}"
            target="_blank"
            class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-body font-500 text-sm px-5 py-2.5 rounded-xl transition-colors">
            Ask us on WhatsApp
        </a>
    </div>
    @endif

    {{-- Part-specific coverage — always shown when a part was specified --}}
    @if(count($result['part_coverage']) > 0)
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6">
        <h3 class="font-display font-700 text-navy text-sm mb-1">
            Coverage for "{{ $result['part_name'] ?: $result['category'] }}"
        </h3>
        <p class="text-xs text-gray-400 mb-3">Exact years and models this part is known to fit, based on our records — shown whether or not we currently have stock.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-200">
                        <th class="py-2 pr-4">Part</th>
                        <th class="py-2 pr-4">Brand</th>
                        <th class="py-2 pr-4">Model</th>
                        <th class="py-2 pr-4">Years Covered</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($result['part_coverage'] as $cov)
                    <tr>
                        <td class="py-2 pr-4 font-500 text-gray-700">{{ $cov['part_name'] }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $cov['brand'] }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $cov['model'] }}</td>
                        <td class="py-2 pr-4 font-mono text-gray-700">{{ $cov['year_range'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Reference: other vehicles that share this engine/transmission --}}
    @if(count($result['interchange_reference']) > 0)
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
        <h3 class="font-display font-700 text-navy text-sm mb-3">Vehicles known to share this powertrain</h3>
        <p class="text-xs text-gray-400 mb-3">For reference — we may not currently stock parts for all of these, but the engine/transmission interchanges.</p>
        <div class="flex flex-wrap gap-2">
            @foreach($result['interchange_reference'] as $ref)
            <span class="bg-white border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">{{ $ref }}</span>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endif

@endsection

@push('scripts')
<script>
// ── Make → Model AJAX cascade ───────────────────────────────────────────
document.getElementById('makeSelect').addEventListener('change', async function() {
    await loadModels(this.value.toUpperCase());
});

async function loadModels(make) {
    const sel = document.getElementById('modelSelect');
    if (!make) {
        sel.innerHTML = '<option value="">Select Model</option>';
        return;
    }
    try {
        const res  = await fetch(`{{ route('parts.models') }}?make=${encodeURIComponent(make.toUpperCase())}`);
        const data = await res.json();
        sel.innerHTML = '<option value="">Select Model</option>' +
            (data.models || []).map(m => `<option value="${m}">${m}</option>`).join('');

        @if(!empty($model))
        sel.value = "{{ $model }}";
        @endif
    } catch(e) {
        sel.innerHTML = '<option value="">Select Model</option>';
    }
}

@if(!empty($make))
loadModels("{{ $make }}");
@endif

// ── VIN decode ───────────────────────────────────────────────────────────
const vinInput   = document.getElementById('vinInput');
const vinCount   = document.getElementById('vinCharCount');
const vinBtn     = document.getElementById('vinDecodeBtn');
const vinStatus  = document.getElementById('vinStatus');

vinInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
    vinCount.textContent = `${this.value.length}/17`;
});

vinBtn.addEventListener('click', async function() {
    const vin = vinInput.value.trim();
    if (vin.length !== 17) {
        vinStatus.textContent = 'VIN must be exactly 17 characters.';
        vinStatus.className = 'text-center text-red-300 text-xs mt-2 font-body';
        return;
    }

    vinStatus.textContent = 'Decoding...';
    vinStatus.className = 'text-center text-gray-300 text-xs mt-2 font-body';

    try {
        const res = await fetch(`{{ route('parts.vin-decode') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ vin })
        });
        const data = await res.json();

        if (data.error || !data.vehicle) {
            vinStatus.textContent = data.error || 'Could not decode this VIN.';
            vinStatus.className = 'text-center text-red-300 text-xs mt-2 font-body';
            return;
        }

        const v = data.vehicle;
        if (v.make)  document.getElementById('makeSelect').value = v.make.toUpperCase();
        if (v.make)  await loadModels(v.make.toUpperCase());
        if (v.model) document.getElementById('modelSelect').value = v.model.toUpperCase();
        if (v.year)  document.getElementById('yearSelect').value = v.year;

        vinStatus.textContent = `Decoded: ${v.year} ${v.make} ${v.model}. Add a part name below if you're looking for something specific, then check compatibility.`;
        vinStatus.className = 'text-center text-green-300 text-xs mt-2 font-body';
    } catch (e) {
        vinStatus.textContent = 'Something went wrong decoding this VIN.';
        vinStatus.className = 'text-center text-red-300 text-xs mt-2 font-body';
    }
});
</script>
@endpush