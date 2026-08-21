<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Labels — {{ count($parts) }} part(s)</title>
<style>
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
.label-small .bc-svg { width:100%; display:flex; justify-content:center; }
.label-small .bc-svg svg { max-width:100%; height:auto; }
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
.oem-reference { padding: 3px 8px 5px; border-top: 1px dashed #ddd; }
.oem-ref-label { font-size: 6.5px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 1px; }
.oem-ref-list { font-size: 7px; color: #888; line-height: 1.3; }

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
.lbl-barcode .bc-svg { width:100%; display:flex; justify-content:center; }
.lbl-barcode .bc-svg svg { max-width:95%; height:auto; }
.lbl-barcode .bc-text { font-size:11px; font-weight:700; letter-spacing:2px; margin-top:2px; font-family:monospace; }

.lbl-footer { padding:5px 12px; border-top:1px solid #ccc; display:flex; justify-content:space-between; align-items:center; font-size:7.5px; color:#888; background:#fafafa; }
.lbl-footer .website { font-weight:700; color:#c9a84c; }

@media print {
    body { background:white; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
    .label-small, .label-large { border:none !important; }

    /* NEW: thermal printers often render text lighter than the on-
       screen preview even at font-weight 900 — thin strokes at small
       sizes get lost in the print/rasterization process. A subtle
       text-stroke thickens every glyph without changing layout, which
       reads as genuinely bolder on thermal output. Print-only — the
       screen preview is unaffected. */
    * { -webkit-text-stroke: 0.35px currentColor; }
    .part-name, .biz-name, .retail, .flag { -webkit-text-stroke: 0.5px currentColor; }
}
@media print {
    body.size-small .label-large { display:none !important; }
    body.size-large .label-small { display:none !important; }
    body.size-small { size:2in 1in; }
    body.size-large { size:4in 6in; }
}
body.size-small .label-large { display:none !important; }
body.size-large .label-small { display:none !important; }
body.size-small .label-tearaway { display:none !important; }
body.size-large .label-tearaway { display:none !important; }
body.size-tearaway .label-small { display:none !important; }
body.size-tearaway .label-large { display:none !important; }

/* ── 4×9 box-style visibility — hide it everywhere except its own
   mode, and hide the other three formats while it's active ── */
body.size-small .label-box49    { display:none !important; }
body.size-large .label-box49    { display:none !important; }
body.size-tearaway .label-box49 { display:none !important; }
body.size-box49 .label-small    { display:none !important; }
body.size-box49 .label-large    { display:none !important; }
body.size-box49 .label-tearaway { display:none !important; }

.label-tearaway {
    width: 4in; background: #fff; border: 1px solid #000;
    font-family: 'Courier New', monospace; font-size: 10px; color: #000;
    margin: 0 auto 20px; page-break-after: always;
}
.tear-section { padding: 10px 12px; }
.tear-section-title { font-weight: 800; font-size: 10.5px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
.tear-row { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 3px; }
.tear-perf { border-top: 2px dashed #000; position: relative; margin: 0; text-align: center; }
.tear-perf::before { content: "✂"; position: absolute; left: -4px; top: -9px; font-size: 12px; background: #fff; padding: 0 3px; }
.tear-perf::after { content: "TEAR HERE"; position: absolute; right: 4px; top: -8px; font-size: 6px; letter-spacing: 1px; color: #999; background: #fff; padding: 0 3px; }
.tear-signature { border-bottom: 1px solid #000; display: inline-block; min-width: 110px; margin-left: 4px; }
.tear-barcode-wrap { text-align: center; margin: 6px 0; }
.tear-barcode-wrap svg { max-width: 100%; height: 32px; }

@media print { body.size-tearaway { size: 4in 9in; } }

/* ═══════════════════════════════════════════════════════
   4×9 BOX STYLE — bordered/boxed layout matching the LKQ
   reference mockup exactly (full frame per section, header
   bar, grid rows, boxed barcode) but with AutoZenith's own
   field names/data throughout. A distinct alternative to the
   card-style tear-away above — pick whichever prints cleaner
   on your stock.
═══════════════════════════════════════════════════════ */
.label-box49 {
    width: 4in; background: #fff; border: 2px solid #000;
    font-family: 'Courier New', monospace; font-size: 9.5px; color: #000;
    margin: 0 auto 20px; page-break-after: always;
}
.box-hdr {
    background: #0d1b2a; color: #fff; padding: 5px 8px;
    font-weight: 800; font-size: 9.5px; text-transform: uppercase;
    letter-spacing: 0.5px; display: flex; justify-content: space-between;
}
.box-hdr .gold { color: #c9a84c; }
.box-grid2 { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #000; }
.box-grid2 .cell { padding: 4px 8px; border-right: 1px solid #000; }
.box-grid2 .cell:last-child { border-right: none; }
.box-grid2 .cell label { display: block; font-size: 6.5px; color: #555; text-transform: uppercase; letter-spacing: 0.3px; }
.box-grid2 .cell .val { font-weight: 700; font-size: 9.5px; }
.box-row-full { padding: 4px 8px; border-top: 1px solid #000; font-size: 9px; }
.box-row-full label { font-size: 6.5px; color: #555; text-transform: uppercase; margin-right: 4px; }
.box-barcode-wrap { text-align: center; padding: 6px 8px; border-top: 1px solid #000; }
.box-barcode-wrap svg { max-width: 100%; height: 34px; }
.box-code { font-size: 9px; letter-spacing: 1px; margin-top: 2px; font-weight: 700; }
.box-remarks { padding: 5px 8px; border-top: 1px solid #000; font-size: 7.5px; line-height: 1.4; color: #333; }

/* Perforation between sections — heavier dashed rule + scissors,
   matching the "TEAR HERE" convention already used on the card-style
   tear-away, styled to read clearly even at thermal-adjacent print
   quality. */
.box-perf { border-top: 2px dashed #000; position: relative; margin: 10px 0 0; }
.box-perf::before { content: "✂"; position: absolute; left: -2px; top: -9px; font-size: 13px; background: #fff; padding: 0 3px; }
.box-perf::after {
    content: "– – TEAR ALONG DOTTED LINE – –";
    position: absolute; left: 50%; top: -8px; transform: translateX(-50%);
    font-size: 6px; letter-spacing: 0.5px; color: #777; background: #fff;
    padding: 0 4px; white-space: nowrap;
}
.box-stub-title {
    text-align: center; padding: 4px 8px 0; font-weight: 800;
    font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px;
}

@media print { body.size-box49 { size: 4in 9in; } }
</style>
</head>
<body id="labelBody">

<div class="controls">
    <h2>🏷 {{ count($parts) }} Label{{ count($parts) !== 1 ? 's' : '' }}</h2>
    <span style="color:#aaa;font-size:11px;">Size:</span>
    <button class="ctrl-btn {{ $size==='small'?'on':'off' }}" onclick="setSize('small')" id="btn-small">2×1" Scan Only</button>
    <button class="ctrl-btn {{ $size==='large'?'on':'off' }}" onclick="setSize('large')" id="btn-large">4×6" Full Label</button>
    <button class="ctrl-btn {{ $size==='tearaway'?'on':'off' }}" onclick="setSize('tearaway')" id="btn-tearaway">✂ Tear-Away (3-Part)</button>
    <button class="ctrl-btn {{ $size==='box49'?'on':'off' }}" onclick="setSize('box49')" id="btn-box49">📦 4×9 Box Style</button>
    <span style="color:#555;">|</span>
    <button class="ctrl-btn print-btn" onclick="window.print()">🖨 Print</button>
    <a href="javascript:history.back()" style="color:#aaa;font-size:11px;text-decoration:none;">← Back</a>
    <span style="color:#444;font-size:10px;margin-left:auto;">Tip: Set printer media to match label size. Disable margins in print dialog.</span>
</div>

<div class="labels-wrap">
@foreach($parts as $part)
@php
    $group    = $part->interchange_group;
    $vehicles = $part->interchange_vehicles; // already merged in BarcodeController
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
    <div class="bc-svg"><svg id="barcode-small-{{ $part->id }}"></svg></div>
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
                @if($part->engine_code_oem)
                    · {{ $part->engine_code_oem }}@if($part->engine_displacement) ({{ $part->engine_displacement }})@endif
                @endif
                @if($part->drive_type) · {{ $part->drive_type }} @endif
                @if($part->side && $part->side !== 'N/A') · {{ $part->side }} @endif
            </div>
            {{-- NEW: transmission code + pin count — was completely
                 absent from the tag before, despite pin_count being
                 explicitly the one proprietary attribute meant to
                 travel with every gearbox. Scoped to Transmission
                 category items only, per standing decision. --}}
            @if($part->part_category === 'Transmission' && ($part->transmission_code_oem || $part->pin_count))
            <div class="part-sub">
                @if($part->transmission_code_oem){{ $part->transmission_code_oem }}@endif
                @if($part->pin_count) · {{ $part->pin_count }}-pin @endif
            </div>
            @endif
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
                @if($group && ($group->source ?? null) === 'platform')
                    ⚙ Chassis platform match: {{ $group->generation ?? $group->group_code }}
                @elseif($group)
                    ✓ Confirmed group: {{ $group->group_code }}
                @else
                    ~ Suggested via OEM code
                @endif
            </div>

            {{-- NEW: OEM reference list — vehicles known to share this
                 engine/transmission code industry-wide, NOT limited to
                 what's actually in your stock. Kept visually distinct
                 from the confirmed/stock section above so staff never
                 confuse "known to share this engine" with "we have it
                 on hand." --}}
            @if($part->oem_reference->isNotEmpty())
            <div class="oem-reference">
                <div class="oem-ref-label">OEM REFERENCE — also known to share {{ $part->engine_code_oem ?: $part->transmission_code_oem}} (verify before selling as interchange)</div>
                <div class="oem-ref-list">{{ $part->oem_reference->implode(' · ') }}</div>
            </div>
            @endif
        @else
            <div class="fits-val" style="color:#bbb;">No interchange data on file — see compatibility checker</div>
        @endif

        {{-- NEW: staff-added free-text "Extra Compatibility Note" —
             shown regardless of whether structured interchange data
             exists above, since a caveat/confirmation can apply either
             way. Each note is attributed to whoever wrote it. --}}
        @if($part->compatibility_notes->isNotEmpty())
        <div class="oem-reference" style="border-top:1px dashed #ddd;">
            <div class="oem-ref-label">STAFF NOTE{{ $part->compatibility_notes->count() > 1 ? 'S' : '' }} — Extra Compatibility</div>
            @foreach($part->compatibility_notes as $note)
            <div class="oem-ref-list" style="margin-bottom:2px;">
                {{ $note->note }}
                <span style="color:#bbb;">— {{ $note->added_by_name ?? 'Staff' }}, {{ \Carbon\Carbon::parse($note->created_at)->format('M Y') }}</span>
            </div>
            @endforeach
        </div>
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
        </div>
        <div class="flags">
            @if($part->is_major_component)<span class="flag major">⚡ MAJOR COMPONENT</span>@endif
            @if($part->legal_trace_required)<span class="flag legal">⚠ LEGAL TRACE REQ.</span>@endif
            <span style="font-size:7px;color:#aaa;margin-top:4px;">Qty: {{ $part->stock_qty }}</span>
        </div>
    </div>

    {{-- Barcode --}}
    <div class="lbl-barcode">
        <div class="bc-svg"><svg id="barcode-large-{{ $part->id }}"></svg></div>
        <div class="bc-text">{{ $part->part_code }}</div>
    </div>

    {{-- Footer --}}
    <div class="lbl-footer">
        <span>autozenithparts.com</span>
        <span>{{ $phone }}</span>
        <span class="website">AUTO ZENITH PARTS</span>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════
     TEAR-AWAY 3-PART TAG — mirrors the LKQ-style tear-off workflow:
     Main Tag stays wired to the part; Stub 1 torn off when the part
     is physically pulled from the shelf; Stub 2 torn off at the
     loading dock and stapled to the route sheet/invoice. This is a
     PHYSICAL PROCESS document, not just a barcode — it now carries
     the audit trail that used to require separate printed Warehouse/
     Accounts/Gate invoice copies.
     ══════════════════════════════════════════════════════════════ --}}
<div class="label-tearaway">

    {{-- ── MAIN TAG — stays wired to the part ── --}}
    <div class="tear-section">
        <div class="tear-section-title">Auto Zenith Parts — Main Tag</div>
        <div class="tear-row"><span>PART #: {{ $part->part_code }}</span><span>STOCK #: {{ $part->part_code }}</span></div>
        <div class="tear-row"><span>DESC: {{ $part->part_name }}</span></div>
        <div class="tear-row">
            <span>{{ $vehicle ?: 'Universal' }}</span>
            <span>GRADE: {{ $part->condition_grade ?? '—' }}</span>
        </div>
        @if($part->engine_code_oem)
        <div class="tear-row"><span>ENGINE: {{ $part->engine_code_oem }}@if($part->engine_displacement) ({{ $part->engine_displacement }})@endif</span></div>
        @endif
        <div class="tear-row">
            <span>VIN: {{ $part->donor_vin ?? '—' }}</span>
            <span>LOC: {{ $binLoc }}</span>
        </div>
        @if($part->mileage)
        <div class="tear-row"><span>MILES: {{ number_format($part->mileage) }}</span></div>
        @endif
        <div class="tear-barcode-wrap">
            <svg id="barcode-tear-main-{{ $part->id }}"></svg>
        </div>
        <div class="tear-row"><span>IC #: {{ $icCode }}</span><span>{{ $part->price_fmt }}</span></div>
        <div class="tear-row" style="margin-top:6px;"><span>DISMANTLER TECH ID:</span><span class="tear-signature"></span></div>
    </div>

    <div class="tear-perf"></div>

    {{-- ── STUB 1 — torn off when physically pulled from shelf ── --}}
    <div class="tear-section">
        <div class="tear-section-title">Stub 1 — Pull Confirmation</div>
        <div class="tear-row"><span>PART #: {{ $part->part_code }}</span><span>STOCK #: {{ $part->part_code }}</span></div>
        <div class="tear-row"><span>{{ $vehicle ?: 'Universal' }}</span></div>
        <div class="tear-row"><span>LOC: {{ $binLoc }}</span></div>
        <div class="tear-barcode-wrap">
            <svg id="barcode-tear-stub1-{{ $part->id }}"></svg>
        </div>
        <div class="tear-row" style="margin-top:6px;"><span>PULLED BY:</span><span class="tear-signature"></span></div>
        <div class="tear-row"><span>DATE:</span><span class="tear-signature"></span></div>
    </div>

    <div class="tear-perf"></div>

    {{-- ── STUB 2 — torn off at loading dock, stapled to route sheet/
         invoice. Covers what used to need separate Warehouse/
         Accounts/Gate invoice copies, all on one stub. ── --}}
    <div class="tear-section">
        <div class="tear-section-title">Stub 2 — Audit &amp; Shipping Receipt</div>
        <div class="tear-row"><span>PART #: {{ $part->part_code }}</span><span>ORDER / INVOICE #:</span></div>
        <div class="tear-barcode-wrap">
            <svg id="barcode-tear-stub2-{{ $part->id }}"></svg>
        </div>
        <div class="tear-row" style="margin-top:6px;"><span>WAREHOUSE VERIFIED BY:</span><span class="tear-signature"></span></div>
        <div class="tear-row"><span>DRIVER / LOADER SIGNATURE:</span><span class="tear-signature"></span></div>
        <div class="tear-row"><span>GATE / SECURITY:</span><span class="tear-signature"></span></div>
        <div class="tear-row"><span>DATE:</span><span class="tear-signature"></span></div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════
     4×9 BOX-STYLE TAG — bordered/boxed layout, same 3-part workflow
     as the tear-away above (Main Tag stays on the part; Stub 1 torn
     at pull; Stub 2 torn at audit/shipping) but built to mirror the
     LKQ reference mockup's framed-box look, using AutoZenith's own
     field names and data throughout — no Hollander numbers, no LKQ
     branding.
     ══════════════════════════════════════════════════════════════ --}}
<div class="label-box49">

    {{-- ── MAIN TAG ── --}}
    <div class="box-hdr">
        <span>AUTO <span class="gold">ZENITH</span> PARTS</span>
        <span>STOCK #: {{ $part->part_code }}</span>
    </div>
    <div class="box-row-full"><label>Desc:</label>{{ $part->part_name }}</div>
    <div class="box-grid2">
        <div class="cell">
            <label>Year / Make / Model</label>
            <div class="val">{{ $vehicle ?: 'Universal' }}</div>
        </div>
        <div class="cell">
            <label>Grade</label>
            <div class="val">{{ $part->condition_grade ?? '—' }}</div>
        </div>
    </div>
    <div class="box-grid2">
        <div class="cell">
            <label>VIN</label>
            <div class="val" style="font-size:8px;">{{ $part->donor_vin ?? '—' }}</div>
        </div>
        <div class="cell">
            <label>Location</label>
            <div class="val">{{ $binLoc }}</div>
        </div>
    </div>
    @if($part->mileage || $part->engine_code_oem)
    <div class="box-row-full">
        @if($part->mileage)<label>Miles:</label>{{ number_format($part->mileage) }}@endif
        @if($part->engine_code_oem)&nbsp;&nbsp;<label>Engine:</label>{{ $part->engine_code_oem }}@if($part->engine_displacement) ({{ $part->engine_displacement }})@endif @endif
    </div>
    @endif
    <div class="box-barcode-wrap">
        <svg id="barcode-box-main-{{ $part->id }}"></svg>
        <div class="box-code">*{{ $part->part_code }}*</div>
    </div>
    @if($fitsStr || $part->conditions_and_options || $part->description)
    <div class="box-remarks">
        <strong>REMARKS:</strong>
        {{ $part->conditions_and_options ?? $part->description ?? '' }}
        @if($fitsStr) {{ $fitsStr ? ' Also fits: '.$fitsStr : '' }} @endif
    </div>
    @endif

    <div class="box-perf"></div>

    {{-- ── STUB 1 — Dismantling / Pulling Tag ── --}}
    <div class="box-stub-title">Stub 1 — Dismantling / Pulling Tag</div>
    <div class="box-grid2" style="border-top:none;">
        <div class="cell">
            <label>Part / Stock #</label>
            <div class="val">{{ $part->part_code }}</div>
        </div>
        <div class="cell">
            <label>Location</label>
            <div class="val">{{ $binLoc }}</div>
        </div>
    </div>
    <div class="box-row-full"><label>Y/M/M:</label>{{ $vehicle ?: 'Universal' }}</div>
    <div class="box-barcode-wrap">
        <svg id="barcode-box-stub1-{{ $part->id }}"></svg>
        <div class="box-code">*{{ $part->part_code }}*</div>
    </div>

    <div class="box-perf"></div>

    {{-- ── STUB 2 — Audit / Order Picking Receipt ── --}}
    <div class="box-stub-title">Stub 2 — Audit / Order Picking Receipt</div>
    <div class="box-grid2" style="border-top:none;">
        <div class="cell">
            <label>Part #</label>
            <div class="val">{{ $part->part_code }}</div>
        </div>
        <div class="cell">
            <label>Stock #</label>
            <div class="val">{{ $part->part_code }}</div>
        </div>
    </div>
    <div class="box-row-full"><label>Date Pulled:</label>____/____/{{ now()->format('Y') }} &nbsp;&nbsp; <label>Tech ID:</label>________________</div>
    <div class="box-barcode-wrap">
        <svg id="barcode-box-stub2-{{ $part->id }}"></svg>
        <div class="box-code">*{{ $part->part_code }}*</div>
    </div>

</div>
@endforeach
</div>

<script>
// ── Checksummed Code128 SVG barcode — same reliable generator used
// elsewhere in the app (bin labels), replacing the "Libre Barcode 128"
// Google Font this label used to rely on. That font-based approach had
// NO checksum digit and could render inconsistently (or fail entirely
// if the font didn't load), which is very likely why a printed label
// wasn't reading on a real scanner. ──
(function (global) {
    const CHARSET = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
    const PATTERNS = ["212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212","112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131","311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321","112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121","313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111","314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114","122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212","124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113","114311","411113","411311","113141","114131","311141","411131","211412","211214","211232","2331112"];
    const START_B = 104, STOP = 106;
    function encode(text) {
        const values = [];
        for (let i = 0; i < text.length; i++) {
            const idx = CHARSET.indexOf(text[i]);
            if (idx === -1) throw new Error(`Unsupported char: ${text[i]}`);
            values.push(idx);
        }
        let checksum = START_B;
        values.forEach((v, i) => { checksum += v * (i + 1); });
        return [START_B, ...values, checksum % 103, STOP];
    }
    global.renderBarcode = function (elementId, text, opts = {}) {
        const svg = document.getElementById(elementId);
        if (!svg) return;
        const barHeight = opts.height || 60, barWidth = opts.width || 2;
        const codes = encode(text);
        let x = 10, bars = '';
        codes.forEach(code => {
            const pattern = PATTERNS[code];
            let isBar = true;
            for (let i = 0; i < pattern.length; i++) {
                const w = parseInt(pattern[i]) * barWidth;
                if (isBar) bars += `<rect x="${x}" y="0" width="${w}" height="${barHeight}" fill="#000"/>`;
                x += w; isBar = !isBar;
            }
        });
        const totalWidth = x + 10, totalHeight = barHeight + 5;
        svg.setAttribute('viewBox', `0 0 ${totalWidth} ${totalHeight}`);
        svg.setAttribute('width', totalWidth);
        svg.setAttribute('height', totalHeight);
        svg.innerHTML = bars;
    };
})(window);

// Render both the small and large barcode for every part on this page.
const PART_CODES = @json($parts->pluck('part_code', 'id'));
Object.keys(PART_CODES).forEach(id => {
    renderBarcode('barcode-small-' + id, PART_CODES[id], { width: 1.6, height: 40 });
    renderBarcode('barcode-large-' + id, PART_CODES[id], { width: 2.6, height: 65 });
    // NEW: tear-away tag — same barcode repeated on all 3 sections,
    // matching the LKQ mockup pattern (each stub stays independently
    // scannable once torn off from the others).
    renderBarcode('barcode-tear-main-'  + id, PART_CODES[id], { width: 1.4, height: 32 });
    renderBarcode('barcode-tear-stub1-' + id, PART_CODES[id], { width: 1.4, height: 32 });
    renderBarcode('barcode-tear-stub2-' + id, PART_CODES[id], { width: 1.4, height: 32 });
    // NEW: 4×9 box-style tag — same 3 sections, same barcode data,
    // rendered independently so each stub still scans once torn off.
    renderBarcode('barcode-box-main-'  + id, PART_CODES[id], { width: 1.6, height: 34 });
    renderBarcode('barcode-box-stub1-' + id, PART_CODES[id], { width: 1.6, height: 34 });
    renderBarcode('barcode-box-stub2-' + id, PART_CODES[id], { width: 1.6, height: 34 });
});

function setSize(size) {
    document.getElementById('btn-small').className = 'ctrl-btn ' + (size==='small'?'on':'off');
    document.getElementById('btn-large').className = 'ctrl-btn ' + (size==='large'?'on':'off');
    document.getElementById('btn-tearaway').className = 'ctrl-btn ' + (size==='tearaway'?'on':'off');
    document.getElementById('btn-box49').className = 'ctrl-btn ' + (size==='box49'?'on':'off');
    document.body.className = 'size-' + size;
    const url = new URL(window.location.href);
    url.searchParams.set('size', size);
    window.history.replaceState({}, '', url);
}
setSize('{{ $size }}');
</script>
</body>
</html>
