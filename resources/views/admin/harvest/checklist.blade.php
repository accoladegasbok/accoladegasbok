{{-- FILE: resources/views/admin/harvest/checklist.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Harvesting Checklist')
@section('page-title', 'Harvesting Checklist')
@section('page-sub', $session->year . ' ' . $session->make . ' ' . $session->model . ' — VIN: ' . $session->vin)

@section('content')

{{-- ── VEHICLE SUMMARY BANNER ─────────────────────────────────────── --}}
<div class="bg-[#1e293b] border border-[#334155] rounded-xl p-4 mb-6 flex flex-wrap gap-4 text-sm">
    <div><span class="text-slate-400">Make / Model</span><br><span class="text-white font-semibold">{{ $session->make }} {{ $session->model }}</span></div>
    <div><span class="text-slate-400">Year</span><br><span class="text-white font-semibold">{{ $session->year }}</span></div>
    <div><span class="text-slate-400">VIN</span><br><span class="text-white font-mono text-xs">{{ $session->vin }}</span></div>
    <div><span class="text-slate-400">Mileage</span><br><span class="text-white font-semibold">{{ number_format($session->mileage) }} mi</span></div>
    <div><span class="text-slate-400">Colour</span><br><span class="text-white font-semibold">{{ $session->colour ?? '—' }}</span></div>
    <div>
        <span class="text-slate-400">Location</span><br>
        <span class="text-white font-semibold">{{ $harvestLocation }}</span>
    </div>
    <div>
        {{-- Currency badge --}}
        <span class="text-slate-400">Currency</span><br>
        <span class="font-bold text-lg"
              style="color: {{ $currency['code'] === 'NGN' ? '#facc15' : ($currency['code'] === 'GHS' ? '#34d399' : '#60a5fa') }}">
            {{ $currency['symbol'] }} {{ $currency['code'] }}
        </span>
    </div>
    @if($oemSuggest['engine_code'])
    <div><span class="text-slate-400">Engine OEM</span><br><span class="text-[#C8960C] font-mono font-bold">{{ $oemSuggest['engine_code'] }}</span></div>
    @endif
    @if($oemSuggest['transmission_code'])
    <div><span class="text-slate-400">Gearbox OEM</span><br><span class="text-[#C8960C] font-mono font-bold">{{ $oemSuggest['transmission_code'] }}@if($oemSuggest['pin_count']) ({{ $oemSuggest['pin_count'] }}-pin)@endif</span></div>
    @endif
</div>

{{-- ── FLASH MESSAGES ─────────────────────────────────────────────── --}}
@if(session('error'))
    <div class="bg-red-900/40 border border-red-500 text-red-300 rounded-lg px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- ── FLOATING SAVE BAR — fixed to viewport, stays visible while scrolling ── --}}
<div class="fixed top-0 left-0 right-0 z-[999] bg-[#0f172a] border-b border-[#334155] px-4 sm:px-8 py-3 flex flex-wrap items-center gap-3 shadow-lg">
    <button type="button" onclick="tickAll()"
        class="px-4 py-2 text-xs rounded-lg bg-[#C8960C]/20 text-[#C8960C] border border-[#C8960C]/30 hover:bg-[#C8960C]/30 transition">
        ✅ Tick All
    </button>
    <button type="button" onclick="untickAll()"
        class="px-4 py-2 text-xs rounded-lg bg-slate-700 text-slate-300 border border-slate-600 hover:bg-slate-600 transition">
        ☐ Untick All
    </button>
    <span class="text-slate-400 text-sm">
        <span id="partCountSticky">0 parts</span> selected —
        Total: <strong class="text-white" id="grandTotalSticky">{{ $currency['symbol'] }}0</strong>
    </span>
    <button type="button" onclick="document.getElementById('harvestForm').requestSubmit()"
        class="ml-auto px-6 py-2.5 rounded-xl bg-[#C8960C] text-black font-bold text-sm hover:bg-yellow-400 transition shadow-md">
        💾 Save Parts to Inventory
    </button>
</div>
{{-- Spacer so fixed bar doesn't overlap page content underneath it --}}
<div class="h-[60px]"></div>

{{-- ── TOOLBAR ─────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center gap-3 mb-5">
    <button type="button" onclick="tickAll()"
        class="px-4 py-2 text-xs rounded-lg bg-[#C8960C]/20 text-[#C8960C] border border-[#C8960C]/30 hover:bg-[#C8960C]/30 transition">
        ✅ Tick All
    </button>
    <button type="button" onclick="untickAll()"
        class="px-4 py-2 text-xs rounded-lg bg-slate-700 text-slate-300 border border-slate-600 hover:bg-slate-600 transition">
        ☐ Untick All
    </button>
    <span class="text-slate-400 text-sm ml-auto">
        <span id="partCount">0 parts</span> selected —
        Total: <strong class="text-white" id="grandTotal">{{ $currency['symbol'] }}0</strong>
    </span>
</div>

{{-- ── MAIN FORM ───────────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('admin.harvest.saveParts', $session->id) }}" id="harvestForm" enctype="multipart/form-data">
    @csrf

    @foreach($partsByCategory as $category => $parts)
    @php $catSlug = Str::slug($category); @endphp

    <div class="mb-4 border border-[#334155] rounded-xl overflow-hidden" id="cat-{{ $catSlug }}">
        {{-- Category header --}}
        <div class="flex items-center justify-between bg-[#1e293b] px-4 py-3 cursor-pointer"
             onclick="toggleCategory('{{ $catSlug }}')">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="cat-chk-{{ $catSlug }}"
                       class="w-4 h-4 rounded accent-[#C8960C]"
                       onclick="event.stopPropagation(); toggleCategoryCheck('{{ $catSlug }}', this.checked)">
                <span class="text-white font-semibold text-sm">{{ $category }}</span>
                <span class="text-xs text-slate-500 cat-count" id="cnt-{{ $catSlug }}">0 / {{ count($parts) }}</span>
            </div>
            <svg class="w-4 h-4 text-slate-400 transition-transform" id="arrow-{{ $catSlug }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        {{-- Parts rows --}}
        <div id="body-{{ $catSlug }}" class="divide-y divide-[#1e293b]">
            @foreach($parts as $part)
            @php $alreadyHarvested = in_array($part['label'], $existing); @endphp

            <div class="part-row flex flex-wrap items-start gap-3 px-4 py-3 bg-[#0f172a] hover:bg-[#1a2640] transition"
                 data-category="{{ $catSlug }}"
                 data-key="{{ $part['key'] }}">

                {{-- Checkbox --}}
                <div class="flex items-center pt-1">
                    <input type="checkbox"
                           name="parts[]"
                           value="{{ $part['key'] }}"
                           class="part-checkbox w-4 h-4 rounded accent-[#C8960C]"
                           data-key="{{ $part['key'] }}"
                           data-category="{{ $catSlug }}"
                           {{ $alreadyHarvested ? 'disabled checked' : '' }}
                           onchange="onPartTick(this)">
                </div>

                {{-- Part label --}}
                <div class="flex-1 min-w-[160px]">
                    <span class="text-sm {{ $alreadyHarvested ? 'text-slate-500 line-through' : 'text-slate-200' }}">
                        {{ $part['label'] }}
                    </span>
                    @if($alreadyHarvested)
                        <span class="ml-2 text-xs text-emerald-400">✓ already listed</span>
                    @endif
                    @if(isset($part['side']))
                        <span class="ml-1 text-xs text-slate-500">({{ $part['side'] }})</span>
                    @endif
                </div>

                {{-- Price input --}}
                <div class="flex items-center gap-1 min-w-[140px]">
                    <span class="text-slate-400 text-sm currency-sym">{{ $currency['symbol'] }}</span>
                    <input type="number"
                           name="prices[{{ $part['key'] }}]"
                           step="{{ $currency['code'] === 'NGN' ? '1000' : '0.01' }}"
                           min="0"
                           placeholder="{{ $currency['code'] === 'NGN' ? '0' : '0.00' }}"
                           class="w-28 bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-sm text-white
                                  focus:outline-none focus:border-[#C8960C] price-input"
                           data-key="{{ $part['key'] }}"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                    <span class="text-xs text-slate-500">{{ $currency['code'] }}</span>
                </div>

                {{-- Condition grade --}}
                <div class="min-w-[80px]">
                    <select name="grades[{{ $part['key'] }}]"
                            class="bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-sm text-slate-300
                                   focus:outline-none focus:border-[#C8960C]"
                            {{ $alreadyHarvested ? 'disabled' : '' }}>
                        <option value="A">A — Like New</option>
                        <option value="B" selected>B — Good</option>
                        <option value="C">C — Fair</option>
                        <option value="D">D — Poor</option>
                    </select>
                </div>

                {{-- OEM fields for Engine / Transmission --}}
                @if($part['category'] === 'Engine')
                <div class="min-w-[120px]">
                    <input type="text"
                           name="oem_engine[{{ $part['key'] }}]"
                           value="{{ $oemSuggest['engine_code'] ?? '' }}"
                           placeholder="Engine code e.g. 2ZR-FE"
                           class="w-full bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-xs
                                  font-mono text-[#C8960C] focus:outline-none focus:border-[#C8960C]"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                </div>
                @endif

                @if($part['category'] === 'Transmission')
                <div class="flex gap-2 min-w-[220px]">
                    <input type="text"
                           name="oem_transmission[{{ $part['key'] }}]"
                           value="{{ $oemSuggest['transmission_code'] ?? '' }}"
                           placeholder="Gearbox code e.g. U341E"
                           class="w-36 bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-xs
                                  font-mono text-[#C8960C] focus:outline-none focus:border-[#C8960C]"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                    <input type="number"
                           name="pin_count[{{ $part['key'] }}]"
                           value="{{ $oemSuggest['pin_count'] ?? '' }}"
                           min="1" max="40"
                           placeholder="Pins"
                           class="w-16 bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-xs
                                  text-slate-300 focus:outline-none focus:border-[#C8960C]"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                </div>
                @endif

                {{-- Bin Location — per item (#A). NOT required by the
                     browser unconditionally — only ticked/selected parts
                     actually need a bin, enforced by the JS submit guard
                     below instead, which checks only checked rows. --}}
                <div class="min-w-[180px]">
                    <select name="bins[{{ $part['key'] }}]"
                            class="harvest-bin-select bg-[#1e293b] border border-[#C8960C] rounded-lg px-2 py-1.5 text-xs text-white
                                   focus:outline-none focus:border-[#C8960C] w-full"
                            {{ $alreadyHarvested ? 'disabled' : '' }}>
                        <option value="">Select bin...</option>
                    </select>
                </div>

                {{-- Photo — required (at least 1), shown to customers --}}
                <div class="min-w-[140px]">
                    <input type="file"
                           name="photos[{{ $part['key'] }}][]"
                           class="harvest-photo-input text-xs text-slate-400 w-full file:bg-[#1e293b] file:border file:border-[#334155] file:rounded file:px-2 file:py-1 file:text-slate-300 file:text-xs"
                           data-part-key="{{ $part['key'] }}"
                           multiple accept="image/*"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                    <div class="text-[10px] text-red-400 mt-0.5 harvest-photo-warning hidden">At least 1 photo required</div>
                </div>

                {{-- Video — optional, one per part --}}
                <div class="min-w-[140px]">
                    <input type="file"
                           name="video[{{ $part['key'] }}]"
                           accept="video/*"
                           class="text-xs text-slate-400 w-full file:bg-[#1e293b] file:border file:border-[#334155] file:rounded file:px-2 file:py-1 file:text-slate-300 file:text-xs"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                </div>

                {{-- Notes --}}
                <div class="w-full mt-1 pl-7">
                    <input type="text"
                           name="part_notes[{{ $part['key'] }}]"
                           placeholder="Notes (optional — e.g. small scratch)"
                           class="w-full bg-transparent border-b border-[#1e293b] px-1 py-0.5 text-xs text-slate-500
                                  focus:outline-none focus:border-[#C8960C] placeholder-slate-700"
                           {{ $alreadyHarvested ? 'disabled' : '' }}>
                </div>

            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- ── CUSTOM PARTS — admin only, to keep naming uniform ───────── --}}
    @if(in_array(session('staff_role'), ['admin', 'manager']))
    <div class="mb-4 border border-[#334155] rounded-xl overflow-hidden">
        <div class="bg-[#1e293b] px-4 py-3 flex items-center justify-between">
            <span class="text-white font-semibold text-sm">➕ Additional / Custom Parts <span class="text-xs text-[#C8960C] font-normal">(admin/manager only)</span></span>
            <button type="button" onclick="addCustomRow()"
                class="text-xs px-3 py-1.5 rounded-lg bg-[#C8960C]/20 text-[#C8960C] border border-[#C8960C]/30 hover:bg-[#C8960C]/30 transition">
                + Add Row
            </button>
        </div>
        <div id="customPartsContainer" class="divide-y divide-[#1e293b] bg-[#0f172a]">
            {{-- rows injected by JS --}}
        </div>
    </div>
    @else
    <div class="mb-4 border border-[#334155] rounded-xl overflow-hidden">
        <div class="bg-[#1e293b] px-4 py-3">
            <span class="text-slate-400 text-sm">Don't see a part on the list? Ask an admin or manager to add it — only they can add custom part names, to keep naming uniform across the system.</span>
        </div>
    </div>
    @endif

    {{-- ── SESSION NOTES ──────────────────────────────────────────── --}}
    <div class="mb-6">
        <label class="block text-slate-400 text-sm mb-1">Session Notes</label>
        <textarea name="session_notes" rows="2"
            class="w-full bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-2 text-sm text-white
                   focus:outline-none focus:border-[#C8960C] placeholder-slate-600"
            placeholder="Overall condition notes, anything unusual about this vehicle..."></textarea>
    </div>

    {{-- ── SUBMIT ──────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-4">
        <button type="submit"
            class="px-8 py-3 rounded-xl bg-[#C8960C] text-black font-bold text-sm hover:bg-yellow-400 transition">
            💾 Save Parts to Inventory
        </button>
        <a href="{{ route('admin.harvest.index') }}"
            class="px-6 py-3 rounded-xl border border-[#334155] text-slate-400 text-sm hover:text-white transition">
            Cancel
        </a>
        <span class="ml-auto text-slate-400 text-sm">
            Total: <strong class="text-white text-lg" id="grandTotal2">{{ $currency['symbol'] }}0</strong>
            <span class="text-xs text-slate-500 ml-1">{{ $currency['code'] }}</span>
        </span>
    </div>

</form>

@endsection

@push('scripts')
<script>
// ── Currency config from blade ────────────────────────────────────
const CURRENCY = {
    code:     "{{ $currency['code'] }}",
    symbol:   "{{ $currency['symbol'] }}",
    rate:     {{ $currency['rate'] }},
    decimals: {{ $currency['decimals'] }},
};

// ── Totals ────────────────────────────────────────────────────────
function formatAmount(val) {
    if (CURRENCY.decimals === 0) return CURRENCY.symbol + Math.round(val).toLocaleString();
    return CURRENCY.symbol + parseFloat(val).toFixed(CURRENCY.decimals);
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.part-checkbox:checked').forEach(chk => {
        const key   = chk.dataset.key;
        const price = parseFloat(document.querySelector(`input[name="prices[${key}]"]`)?.value || 0);
        total += price;
    });
    // Also add custom rows
    document.querySelectorAll('.custom-price').forEach(inp => {
        total += parseFloat(inp.value || 0);
    });

    const fmt = formatAmount(total);
    document.getElementById('grandTotal').textContent  = fmt;
    document.getElementById('grandTotal2').textContent = fmt;
    const grandTotalSticky = document.getElementById('grandTotalSticky');
    if (grandTotalSticky) grandTotalSticky.textContent = fmt;

    // Part count
    const count = document.querySelectorAll('.part-checkbox:checked').length
                + document.querySelectorAll('.custom-name').length;
    document.getElementById('partCount').textContent = count + ' part' + (count !== 1 ? 's' : '') + ' selected';
    const partCountSticky = document.getElementById('partCountSticky');
    if (partCountSticky) partCountSticky.textContent = count + ' part' + (count !== 1 ? 's' : '') + ' selected';
}

// ── Part checkbox toggle ──────────────────────────────────────────
function onPartTick(chk) {
    const key      = chk.dataset.key;
    const catSlug  = chk.dataset.category;
    const priceInp = document.querySelector(`input[name="prices[${key}]"]`);

    if (chk.checked && priceInp && !priceInp.value) {
        priceInp.focus();
    }
    updateCatCount(catSlug);
    updateTotal();
}

// ── Category collapse ─────────────────────────────────────────────
function toggleCategory(slug) {
    const body  = document.getElementById('body-' + slug);
    const arrow = document.getElementById('arrow-' + slug);
    const open  = body.style.display !== 'none';
    body.style.display  = open ? 'none' : 'block';
    arrow.style.transform = open ? 'rotate(-90deg)' : '';
}

function updateCatCount(slug) {
    const total   = document.querySelectorAll(`.part-row[data-category="${slug}"] .part-checkbox`).length;
    const checked = document.querySelectorAll(`.part-row[data-category="${slug}"] .part-checkbox:checked`).length;
    const el = document.getElementById('cnt-' + slug);
    if (el) el.textContent = checked + ' / ' + total;
}

function toggleCategoryCheck(slug, checked) {
    document.querySelectorAll(`.part-row[data-category="${slug}"] .part-checkbox:not(:disabled)`)
        .forEach(chk => { chk.checked = checked; });
    updateCatCount(slug);
    updateTotal();
}

// ── Tick / untick all ─────────────────────────────────────────────
function tickAll() {
    document.querySelectorAll('.part-checkbox:not(:disabled)').forEach(chk => { chk.checked = true; });
    document.querySelectorAll('[id^="cat-chk-"]').forEach(chk => { chk.checked = true; });
    document.querySelectorAll('[id^="cnt-"]').forEach(el => {
        const slug  = el.id.replace('cnt-', '');
        updateCatCount(slug);
    });
    updateTotal();
}

function untickAll() {
    document.querySelectorAll('.part-checkbox:not(:disabled):checked').forEach(chk => { chk.checked = false; });
    document.querySelectorAll('[id^="cat-chk-"]').forEach(chk => { chk.checked = false; });
    document.querySelectorAll('[id^="cnt-"]').forEach(el => {
        const slug = el.id.replace('cnt-', '');
        updateCatCount(slug);
    });
    updateTotal();
}

// ── Custom parts ──────────────────────────────────────────────────
const PART_NAMES = {!! json_encode(\App\Data\PartNames::flat()) !!};

let customIdx = 0;
function addCustomRow() {
    const i = customIdx++;
    const row = document.createElement('div');
    row.className = 'flex flex-wrap items-center gap-2 px-4 py-3';
    row.innerHTML = `
        <input type="text" name="custom_parts[${i}][name]" placeholder="Select a standard name, or type a new one" required
               list="customPartNames-${i}"
               class="custom-name flex-1 min-w-[160px] bg-[#1e293b] border border-[#334155] rounded-lg
                      px-2 py-1.5 text-sm text-white focus:outline-none focus:border-[#C8960C]">
        <datalist id="customPartNames-${i}">
            ${PART_NAMES.map(n => `<option value="${n}"></option>`).join('')}
        </datalist>
        <div class="flex items-center gap-1">
            <span class="text-slate-400 text-sm">${CURRENCY.symbol}</span>
            <input type="number" name="custom_parts[${i}][price]" placeholder="Price" step="${CURRENCY.decimals === 0 ? '1000' : '0.01'}" min="0"
                   class="custom-price w-28 bg-[#1e293b] border border-[#334155] rounded-lg
                          px-2 py-1.5 text-sm text-white focus:outline-none focus:border-[#C8960C]"
                   oninput="updateTotal()">
            <span class="text-xs text-slate-500">${CURRENCY.code}</span>
        </div>
        <select name="custom_parts[${i}][category]"
                class="bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-sm text-slate-300
                       focus:outline-none focus:border-[#C8960C]">
            <option>Engine</option><option>Transmission</option><option>Body</option>
            <option>Electrical</option><option>Suspension</option><option>Brakes</option>
            <option>Interior</option><option>Cooling</option><option>Fuel</option>
            <option>Exhaust</option><option>Wheels</option><option>Other</option>
        </select>
        <select name="custom_parts[${i}][grade]"
                class="bg-[#1e293b] border border-[#334155] rounded-lg px-2 py-1.5 text-sm text-slate-300
                       focus:outline-none focus:border-[#C8960C]">
            <option value="A">A</option><option value="B" selected>B</option>
            <option value="C">C</option><option value="D">D</option>
        </select>
        <select name="custom_parts[${i}][bin_id]" required
                class="harvest-bin-select bg-[#1e293b] border border-[#C8960C] rounded-lg px-2 py-1.5 text-xs text-white
                       focus:outline-none focus:border-[#C8960C] min-w-[160px]">
            <option value="">Select bin...</option>
        </select>
        <div class="min-w-[140px]">
            <input type="file" name="custom_parts[${i}][photos][]" class="custom-photo-input text-xs text-slate-400 w-full
                   file:bg-[#1e293b] file:border file:border-[#334155] file:rounded file:px-2 file:py-1 file:text-slate-300 file:text-xs"
                   data-custom-idx="${i}" multiple accept="image/*">
            <div class="text-[10px] text-red-400 mt-0.5 custom-photo-warning hidden">At least 1 photo required</div>
        </div>
        <div class="min-w-[140px]">
            <input type="file" name="custom_parts[${i}][video]" accept="video/*" class="text-xs text-slate-400 w-full
                   file:bg-[#1e293b] file:border file:border-[#334155] file:rounded file:px-2 file:py-1 file:text-slate-300 file:text-xs">
        </div>
        <input type="text" name="custom_parts[${i}][note]" placeholder="Notes"
               class="flex-1 min-w-[120px] bg-transparent border-b border-[#334155] px-1 py-1 text-xs
                      text-slate-500 focus:outline-none focus:border-[#C8960C] placeholder-slate-700">
        <button type="button" onclick="this.closest('div').remove(); updateTotal()"
                class="text-red-400 hover:text-red-300 text-lg leading-none">×</button>
    `;
    document.getElementById('customPartsContainer').appendChild(row);
    loadHarvestRooms(); // populate the new row's bin dropdown too
    setTimeout(enforceBinExclusivityAcrossRows, 800);
}

// ── Price input listener ──────────────────────────────────────────
document.addEventListener('input', function(e) {
    if (e.target.matches('input[name^="prices["]') || e.target.matches('.custom-price')) {
        updateTotal();
    }
});

// ── Init ──────────────────────────────────────────────────────────
document.querySelectorAll('[id^="cnt-"]').forEach(el => {
    updateCatCount(el.id.replace('cnt-', ''));
});
updateTotal();

// ── Bin location selector — per item (#A) ──────────────────────────
const HARVEST_LOCATION = "{{ $harvestLocation }}";

async function loadHarvestRooms() {
    try {
        const res = await fetch(`/admin/storage/all-bins-for-location?location=${encodeURIComponent(HARVEST_LOCATION)}`);
        const data = await res.json();
        const bins = data.bins || [];

        const optionsHtml = '<option value="">Select bin...</option>' +
            bins.map(b => `<option value="${b.id}">${b.room_name} — ${b.full_bin_code}</option>`).join('');

        document.querySelectorAll('.harvest-bin-select').forEach(sel => {
            sel.innerHTML = optionsHtml;
        });

        if (bins.length === 0) {
            document.querySelectorAll('.harvest-bin-select').forEach(sel => {
                sel.innerHTML = '<option value="">No bins set up for this location yet</option>';
            });
        }
    } catch (e) {
        document.querySelectorAll('.harvest-bin-select').forEach(sel => {
            sel.innerHTML = '<option value="">Could not load bins</option>';
        });
    }
}

document.addEventListener('DOMContentLoaded', loadHarvestRooms);
loadHarvestRooms();

// ── Prevent the same bin being claimed by two rows in the same
// unsaved batch — bin exclusivity in the database only knows about
// ALREADY-SAVED parts, not what's still sitting unsaved in this form.
// As soon as one row picks a bin, remove that option from every
// other row's dropdown; restore it if deselected.
function enforceBinExclusivityAcrossRows() {
    const selects = document.querySelectorAll('.harvest-bin-select');
    const chosen = new Set();
    selects.forEach(sel => { if (sel.value) chosen.add(sel.value); });

    selects.forEach(sel => {
        const myValue = sel.value;
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return; // skip the placeholder
            const takenByAnother = chosen.has(opt.value) && opt.value !== myValue;
            opt.disabled = takenByAnother;
            const baseLabel = opt.textContent.replace(' (already selected on this page)', '');
            opt.textContent = baseLabel + (takenByAnother ? ' (already selected on this page)' : '');
        });
    });
}

document.addEventListener('change', function(e) {
    if (e.target.classList && e.target.classList.contains('harvest-bin-select')) {
        enforceBinExclusivityAcrossRows();
    }
});

// Run once now that bins have loaded, and call it again every time
// loadHarvestRooms() is re-run elsewhere (e.g. when a new custom-part
// row is added) by piggybacking on the same call sites — simplest
// fix is just running it shortly after each fetch completes.
setTimeout(enforceBinExclusivityAcrossRows, 800);

document.getElementById('harvestForm').addEventListener('submit', function(e) {
    // Only checked (ticked) rows need a bin and photos — unticked rows aren't being saved.
    let missingBin = false;
    let missingPhotos = [];

    document.querySelectorAll('.part-checkbox:checked:not(:disabled)').forEach(chk => {
        const key = chk.dataset.key;

        const binSelect = document.querySelector(`select[name="bins[${key}]"]`);
        if (binSelect && !binSelect.value) missingBin = true;

        const photoInput = document.querySelector(`.harvest-photo-input[data-part-key="${key}"]`);
        const warning = photoInput ? photoInput.parentElement.querySelector('.harvest-photo-warning') : null;
        const count = photoInput ? photoInput.files.length : 0;
        if (photoInput && count < 1) {
            missingPhotos.push(key);
            if (warning) warning.classList.remove('hidden');
        } else if (warning) {
            warning.classList.add('hidden');
        }
    });

    // Custom parts rows — at least 1 photo
    document.querySelectorAll('.custom-photo-input').forEach(input => {
        const warning = input.parentElement.querySelector('.custom-photo-warning');
        const count = input.files.length;
        if (count < 1) {
            missingPhotos.push('custom-' + input.dataset.customIdx);
            if (warning) warning.classList.remove('hidden');
        } else if (warning) {
            warning.classList.add('hidden');
        }
    });

    if (missingBin) {
        e.preventDefault();
        alert('Select a bin location for every ticked part before saving.');
        return;
    }
    if (missingPhotos.length > 0) {
        e.preventDefault();
        alert(`Every part needs at least 1 photo before saving — check ${missingPhotos.length} item(s) highlighted in red.`);
    }
});
</script>
@endpush
