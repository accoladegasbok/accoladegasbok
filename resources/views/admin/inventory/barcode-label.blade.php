<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Labels — {{ count($parts) }} part(s)</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Inter:wght@400;600;700;900&display=swap');
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',Arial,sans-serif; background:#f5f5f5; font-size:12px; }

.controls {
    position:fixed; top:0; left:0; right:0; z-index:999;
    background:#0d1b2a; color:white;
    padding:10px 20px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.controls h2 { font-size:13px; font-weight:700; color:#c9a84c; }
.ctrl-btn { padding:6px 14px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:none; letter-spacing:0.5px; text-transform:uppercase; }
.ctrl-btn.on  { background:#c9a84c; color:#0d1b2a; }
.ctrl-btn.off { background:#1e3a5f; color:#aaa; border:1px solid #334; }
.print-btn    { background:#1a6b3c; color:white; }
.labels-wrap  { margin-top:58px; padding:16px; display:flex; flex-wrap:wrap; gap:8px; }

/* ═══════════════════════════════════════════════════════
   2×1 — scan-only shelf tag with year/make/model
═══════════════════════════════════════════════════════ */
.label-small {
    width:2in; height:1in;
    background:white; border:1px solid #ccc;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:2px 4px; page-break-inside:avoid; overflow:hidden;
}
.label-small .vehicle { font-size:6px; font-weight:700; color:#555; text-align:center; margin-bottom:1px; white-space:nowrap; overflow:hidden; }
.label-small .bc { font-family:'Libre Barcode 128',monospace; font-size:40px; line-height:1; color:#000; width:100%; text-align:center; }
.label-small .code { font-size:7px; font-weight:700; color:#000; letter-spacing:1px; margin-top:1px; text-align:center; }
.label-small .name { font-size:5px; color:#777; text-align:center; margin-top:1px; }

/* ═══════════════════════════════════════════════════════
   4×6 — Powerlink Fenix style, Auto Zenith branding
═══════════════════════════════════════════════════════ */
.label-large {
    width:4in; height:6in;
    background:white; border:1px solid #999;
    display:flex; flex-direction:column;
    page-break-inside:avoid; overflow:hidden;
}
.lbl-biz { padding:10px 12px 8px; border-bottom:1.5px solid #000; }
.lbl-biz .biz-name { font-size:14px; font-weight:900; letter-spacing:1px; color:#0d1b2a; }
.lbl-biz .biz-name span { color:#c9a84c; }
.lbl-biz .biz-addr { font-size:9px; color:#555; margin-top:2px; line-height:1.5; }

.lbl-route { display:grid; grid-template-columns:1fr 1fr; border-bottom:1px solid #999; font-size:9px; }
.lbl-route .cell { padding:4px 10px; }
.lbl-route .cell:first-child { border-right:1px solid #999; }
.lbl-route .cell label { display:block; font-size:7.5px; color:#777; margin-bottom:1px; text-transform:uppercase; }
.lbl-route .cell .val { font-weight:700; }

.lbl-part { padding:10px 12px 8px; border-bottom:1px solid #999; display:flex; align-items:flex-start; gap:8px; }
.lbl-part .part-info { flex:1; }
.lbl-part .part-name { font-size:15px; font-weight:900; line-height:1.25; }
.lbl-part .part-sub  { font-size:9.5px; color:#444; margin-top:3px; line-height:1.5; }
.lbl-part .grade-box { width:36px; height:36px; border:2.5px solid #000; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:900; flex-shrink:0; margin-top:2px; }
.grade-box.A { border-color:#2e7d32; color:#2e7d32; }
.grade-box.B { border-color:#e65100; color:#e65100; }
.grade-box.C { border-color:#c62828; color:#c62828; }

.lbl-ids { display:grid; grid-template-columns:1fr 1fr 1fr; border-bottom:1px solid #999; font-size:9px; }
.lbl-ids .cell { padding:5px 10px; border-right:1px solid #999; }
.lbl-ids .cell:last-child { border-right:none; }
.lbl-ids .cell label { display:block; font-size:7.5px; color:#777; margin-bottom:1px; text-transform:uppercase; }
.lbl-ids .cell .val { font-weight:700; font-size:10px; font-family:monospace; }

.lbl-fits { padding:6px 12px; border-bottom:1px solid #999; font-size:8.5px; }
.lbl-fits label { font-size:7.5px; color:#777; text-transform:uppercase; display:block; margin-bottom:2px; }
.lbl-fits .fits-val { line-height:1.6; color:#222; }
.lbl-fits .ic-source { font-size:7px; color:#aaa; margin-top:1px; }

.lbl-notes { padding:5px 12px; border-bottom:1px solid #999; font-size:8px; color:#555; min-height:22px; }
.lbl-notes label { font-size:7px; color:#aaa; text-transform:uppercase; margin-right:4px; }

.lbl-price { padding:8px 12px; border-bottom:1px solid #999; display:flex; align-items:center; justify-content:space-between; }
.lbl-price .retail { font-size:22px; font-weight:900; }
.lbl-price .trade  { font-size:10px; color:#555; margin-top:2px; }
.lbl-price .flags  { display:flex; flex-direction:column; gap:3px; align-items:flex-end; }
.flag { font-size:7px; font-weight:700; padding:2px 5px; border-radius:3px; }
.flag.major { background:#fff8e1; color:#e65100; border:1px solid #ffcc80; }
.flag.legal { background:#fce4ec; color:#c62828; border:1px solid #ef9a9a; }

.lbl-barcode { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:8px 12px 6px; }
.lbl-barcode .bc { font-family:'Libre Barcode 128',monospace; font-size:68px; line-height:1; color:#000; text-align:center; width:100%; }
.lbl-barcode .bc-text { font-size:11px; font-weight:700; letter-spacing:2px; margin-top:2px; font-family:monospace; }

.lbl-footer { padding:5px 12px; border-top:1px solid #ccc; display:flex; justify-content:space-between; align-items:center; font-size:7.5px; color:#888; background:#fafafa; }
.lbl-footer .website { font-weight:700; color:#c9a84c; }

@media print {
    body { background:white; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
    .label-small, .label-large { border:none !important; }
}
@media print {
    body.size-small .label-large { display:none !important; }
    body.size-large .label-small { display:none !important; }
    body.size-small { size:2in 1in; }
    body.size-large { size:4in 6in; }
}
body.size-small .label-large { display:none; }
body.size-large .label-small { display:none; }
</style>
</head>
<body id="labelBody">

<div class="controls">
    <h2>🏷 {{ count($parts) }} Label{{ count($parts) !== 1 ? 's' : '' }}</h2>
    <span style="color:#aaa;font-size:11px;">Size:</span>
    <button class="ctrl-btn {{ $size==='small'?'on':'off' }}" onclick="setSize('small')" id="btn-small">2×1" Scan Only</button>
    <button class="ctrl-btn {{ $size==='large'?'on':'off' }}" onclick="setSize('large')" id="btn-large">4×6" Full Label</button>
    <span style="color:#555;">|</span>
    <button class="ctrl-btn print-btn" onclick="window.print()">🖨 Print</button>
    <a href="javascript:history.back()" style="color:#aaa;font-size:11px;text-decoration:none;">← Back</a>
    <span style="color:#444;font-size:10px;margin-left:auto;">Tip: Set printer media to match label size. Disable margins in print dialog.</span>
</div>

<div class="labels-wrap">
@foreach($parts as $part)
@php
    $group    = $part->interchange_group;
    $vehicles = $part->interchange_vehicles;
    $fitsStr  = $vehicles->map(fn($v) => trim(($v->make??'').' '.($v->model??'').' ('.($v->year_from??'').'-'.($v->year_to??'').')'))->implode(' · ');
    $icCode   = $group?->group_code ?? $part->engine_code_oem ?? $part->transmission_code_oem ?? '—';
    $binLoc   = $part->bin_location ?? '—';
    $sym      = $part->sym;
    $grade    = $part->condition_grade ?? 'B';
    $vehicle  = trim(($part->brand??'').' '.($part->model??'').' '.($part->year_from ?? '').($part->year_to && $part->year_to!=$part->year_from?'–'.$part->year_to:''));
@endphp

{{-- 2×1 SMALL — barcode + vehicle + part name --}}
<div class="label-small">
    <div class="vehicle">{{ $vehicle ?: 'UNIVERSAL' }}</div>
    <div class="bc">{{ $part->part_code }}</div>
    <div class="code">{{ $part->part_code }}</div>
    <div class="name">{{ Str::limit($part->part_name, 35) }}</div>
</div>

{{-- 4×6 LARGE — Powerlink Fenix style, AUTO ZENITH branding --}}
<div class="label-large">

    {{-- Auto Zenith header (not Gasbok) --}}
    <div class="lbl-biz">
        <div class="biz-name">AUTO <span>ZENITH</span> PARTS</div>
        <div class="biz-addr">
            {{ $part->location ?? '' }}
            @php
                $phone = match(true) {
                    str_contains($part->location ?? '', 'Nigeria') ||
                    str_contains($part->location ?? '', 'Ghana')   => '+234 915 568 8804',
                    default                                         => '+1 (682) 256-3201',
                };
            @endphp
            · {{ $phone }} · autozenithparts.com
        </div>
    </div>

    {{-- Location / Printed row --}}
    <div class="lbl-route">
        <div class="cell">
            <label>Location</label>
            <div class="val">{{ $part->location ?? '—' }}</div>
        </div>
        <div class="cell">
            <label>Printed</label>
            <div class="val">{{ now()->format('m/d/y g:i A') }}</div>
        </div>
    </div>

    {{-- Part name + grade --}}
    <div class="lbl-part">
        <div class="part-info">
            <div class="part-name">{{ $part->part_name }}</div>
            <div class="part-sub">
                {{ $vehicle ?: 'Universal' }}
                @if($part->engine_code_oem) · {{ $part->engine_code_oem }} @endif
                @if($part->side && $part->side !== 'N/A') · {{ $part->side }} @endif
            </div>
            @if($part->mileage)<div class="part-sub">Mileage: {{ number_format($part->mileage) }} mi</div>@endif
        </div>
        <div class="grade-box {{ $grade }}">{{ $grade }}</div>
    </div>

    {{-- Stock # / IC # / Bin --}}
    <div class="lbl-ids">
        <div class="cell">
            <label>Stock #</label>
            <div class="val">{{ $part->part_code }}</div>
        </div>
        <div class="cell">
            <label>IC #</label>
            <div class="val">{{ $icCode }}</div>
        </div>
        <div class="cell">
            <label>Located</label>
            <div class="val">{{ $binLoc }}</div>
        </div>
    </div>

    {{-- Also fits --}}
    <div class="lbl-fits">
        <label>Also Fits (Interchange)</label>
        @if($fitsStr)
            <div class="fits-val">{{ $fitsStr }}</div>
            <div class="ic-source">
                {{ $group ? '✓ Confirmed group: '.$group->group_code : '~ Suggested via OEM code' }}
            </div>
        @else
            <div class="fits-val" style="color:#bbb;">No interchange data on file — see compatibility checker</div>
        @endif
    </div>

    {{-- Conditions --}}
    <div class="lbl-notes">
        <label>Note:</label>
        {{ $part->conditions_and_options ?? $part->description ?? '—' }}
        @if($part->donor_vin)
            &nbsp;·&nbsp;<span style="font-family:monospace;font-size:7px;">VIN: {{ $part->donor_vin }}</span>
        @endif
    </div>

    {{-- Price + flags --}}
    <div class="lbl-price">
        <div>
            <div class="retail">{{ $part->price_fmt }}</div>
            @if($part->wholesale_fmt)
            <div class="trade">Trade: {{ $part->wholesale_fmt }}</div>
            @endif
        </div>
        <div class="flags">
            @if($part->is_major_component)<span class="flag major">⚡ MAJOR COMPONENT</span>@endif
            @if($part->legal_trace_required)<span class="flag legal">⚠ LEGAL TRACE REQ.</span>@endif
            <span style="font-size:7px;color:#aaa;margin-top:4px;">Qty: {{ $part->stock_qty }}</span>
        </div>
    </div>

    {{-- Barcode --}}
    <div class="lbl-barcode">
        <div class="bc">{{ $part->part_code }}</div>
        <div class="bc-text">{{ $part->part_code }}</div>
    </div>

    {{-- Footer --}}
    <div class="lbl-footer">
        <span>autozenithparts.com</span>
        <span>{{ $phone }}</span>
        <span class="website">AUTO ZENITH PARTS</span>
    </div>

</div>
@endforeach
</div>

<script>
function setSize(size) {
    document.getElementById('btn-small').className = 'ctrl-btn ' + (size==='small'?'on':'off');
    document.getElementById('btn-large').className = 'ctrl-btn ' + (size==='large'?'on':'off');
    document.body.className = 'size-' + size;
    const url = new URL(window.location.href);
    url.searchParams.set('size', size);
    window.history.replaceState({}, '', url);
}
setSize('{{ $size }}');
</script>
</body>
</html>
