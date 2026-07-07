{{-- FILE: resources/views/admin/inventory/barcode-label.blade.php
     Prints barcode labels for parts inventory.
     Two sizes:
       - 2x1 inches: barcode only (plain tag, fast scan at gate/shelf)
       - 4x6 inches: barcode + full product info (customer-facing label, dispatch)
     Pass ?size=small for 2x1, ?size=large for 4x6 (default: large)
     Pass ?ids=1,2,3 for batch printing multiple parts
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barcode Label{{ count($parts) > 1 ? 's' : '' }} — {{ count($parts) }} item{{ count($parts) !== 1 ? 's' : '' }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; background: #f5f5f5; }

/* ── Print controls (screen only) ──────────────────────────────── */
.print-controls {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #0d1b2a; color: white; padding: 10px 20px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.print-controls h2 { font-size: 13px; font-weight: 700; color: #c9a84c; }
.ctrl-btn {
    padding: 6px 14px; border-radius: 6px; font-size: 11px; font-weight: 700;
    cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px;
    border: none; transition: all 0.2s;
}
.ctrl-btn.active { background: #c9a84c; color: #0d1b2a; }
.ctrl-btn:not(.active) { background: #1e3a5f; color: #aaa; border: 1px solid #334; }
.ctrl-btn:hover:not(.active) { background: #2a4a7f; color: white; }
.print-btn { background: #1a6b3c; color: white; }
.print-btn:hover { background: #22873d; }
.labels-page { margin-top: 56px; padding: 16px; display: flex; flex-wrap: wrap; gap: 8px; }

/* ═══════════════════════════════════════════════════════════════
   SMALL LABEL — 2 × 1 inches
   Barcode only: part_code, brand/model/year, minimal text
   Use for: shelf tags, gate-pass scanning, quick bin labels
   ═══════════════════════════════════════════════════════════════ */
.label-small {
    width: 2in;
    height: 1in;
    background: white;
    border: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3px 4px;
    page-break-inside: avoid;
    overflow: hidden;
}
.label-small .lbl-code {
    font-family: 'Libre Barcode 128', monospace;
    font-size: 38px;
    line-height: 1;
    letter-spacing: 0;
    color: #000;
    width: 100%;
    text-align: center;
}
.label-small .lbl-ref {
    font-size: 7px;
    font-weight: 700;
    color: #000;
    letter-spacing: 0.5px;
    margin-top: 1px;
    text-align: center;
}
.label-small .lbl-vehicle {
    font-size: 6px;
    color: #555;
    text-align: center;
    margin-top: 1px;
}

/* ═══════════════════════════════════════════════════════════════
   LARGE LABEL — 4 × 6 inches (landscape: 6w × 4h)
   Barcode + full product info: customer-facing dispatch label
   Use for: packing, dispatch, customer pickup, display shelves
   ═══════════════════════════════════════════════════════════════ */
.label-large {
    width: 6in;
    height: 4in;
    background: white;
    border: 1px solid #ccc;
    display: grid;
    grid-template-rows: auto 1fr auto;
    padding: 0;
    page-break-inside: avoid;
    overflow: hidden;
    position: relative;
}
.label-large .lbl-header {
    background: #0d1b2a;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 12px;
}
.label-large .lbl-header .brand {
    font-size: 14px;
    font-weight: 900;
    letter-spacing: 2px;
    color: white;
}
.label-large .lbl-header .brand span { color: #c9a84c; }
.label-large .lbl-header .inv-ref {
    font-size: 11px;
    font-weight: 700;
    color: #c9a84c;
    font-family: monospace;
}
.label-large .lbl-body {
    display: grid;
    grid-template-columns: 2.6in 1fr;
    gap: 0;
    overflow: hidden;
}
.label-large .lbl-barcode-col {
    padding: 10px 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 1px dashed #ddd;
}
.label-large .lbl-barcode-col .barcode-font {
    font-family: 'Libre Barcode 128', monospace;
    font-size: 64px;
    line-height: 1;
    color: #000;
    text-align: center;
}
.label-large .lbl-barcode-col .barcode-text {
    font-size: 9px;
    font-weight: 700;
    color: #333;
    letter-spacing: 1px;
    margin-top: 2px;
    text-align: center;
}
.label-large .lbl-info-col {
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.label-large .lbl-part-name {
    font-size: 14px;
    font-weight: 900;
    color: #0d1b2a;
    line-height: 1.2;
}
.label-large .lbl-vehicle-info {
    font-size: 11px;
    color: #555;
    line-height: 1.5;
}
.label-large .lbl-vehicle-info strong { color: #0d1b2a; }
.label-large .lbl-grade-row {
    display: flex;
    gap: 6px;
    align-items: center;
    margin-top: 4px;
    flex-wrap: wrap;
}
.grade-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}
.grade-A { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.grade-B { background: #fff3e0; color: #e65100; border: 1px solid #ffcc80; }
.grade-C { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
.lbl-price {
    font-size: 20px;
    font-weight: 900;
    color: #0d1b2a;
    margin-top: auto;
}
.lbl-price .price-label { font-size: 9px; color: #888; font-weight: 400; }
.lbl-price .trade-price { font-size: 13px; color: #c9a84c; margin-top: 1px; }
.label-large .lbl-footer {
    background: #f8f9fa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 12px;
}
.label-large .lbl-footer .lbl-location {
    font-size: 9px;
    color: #666;
}
.label-large .lbl-footer .lbl-website {
    font-size: 9px;
    font-weight: 700;
    color: #c9a84c;
}
.label-large .lbl-footer .lbl-condition {
    font-size: 8px;
    color: #888;
    max-width: 2in;
    text-align: right;
}
.legal-badge {
    padding: 2px 6px; border-radius: 3px;
    font-size: 8px; font-weight: 700;
    background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a;
}
.major-badge {
    padding: 2px 6px; border-radius: 3px;
    font-size: 8px; font-weight: 700;
    background: #fff8e1; color: #f57f17; border: 1px solid #ffe082;
}

/* ── Barcode font from Google (screen + print) ────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap');

/* ── PRINT RULES ────────────────────────────────────────────────
   Each label prints true to its physical size.
   The page size is set per label type via JS before printing.
──────────────────────────────────────────────────────────────── */
@media print {
    body { background: white; }
    .print-controls { display: none !important; }
    .labels-page { margin: 0; padding: 0; gap: 0; }
}

/* Small: print on 2x1 stock */
body.print-small .labels-page { display: block; }
body.print-small .label-large { display: none !important; }

/* Large: print on 4x6 stock */
body.print-large .labels-page { display: block; }
body.print-large .label-small { display: none !important; }

@media print {
    body.print-small { size: 2in 1in; }
    body.print-large { size: 6in 4in; }
    body.print-small .label-small,
    body.print-large .label-large {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
</head>
<body id="labelBody">

<div class="print-controls">
    <h2>🏷 Barcode Label{{ count($parts) > 1 ? 's' : '' }} ({{ count($parts) }})</h2>
    <span style="color:#aaa;font-size:11px;">Label size:</span>
    <button class="ctrl-btn {{ $size === 'small' ? 'active' : '' }}" onclick="setSize('small')" id="btn-small">2×1" (Scan Only)</button>
    <button class="ctrl-btn {{ $size === 'large' ? 'active' : '' }}" onclick="setSize('large')" id="btn-large">4×6" (Full Info)</button>
    <span style="color:#444;">|</span>
    <button class="ctrl-btn print-btn" onclick="window.print()">🖨 Print</button>
    <a href="{{ url()->previous() }}" style="color:#aaa;font-size:11px;text-decoration:none;margin-left:8px;">← Back</a>
    <span style="color:#555;font-size:10px;margin-left:auto;">
        Tip: Set printer paper to match label size. Disable margins in print dialog.
    </span>
</div>

<div class="labels-page" id="labelsPage">
    @foreach($parts as $part)
    @php
        $symbols = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
        $sym     = $symbols[$part->currency_code ?? 'NGN'] ?? '₦';
        $retail  = $sym . ($part->currency_code === 'NGN'
            ? number_format(round($part->price_local))
            : number_format($part->price_local, 2));
        $wholesale = $part->price_wholesale
            ? $sym . ($part->currency_code === 'NGN'
                ? number_format(round($part->price_wholesale))
                : number_format($part->price_wholesale, 2))
            : null;
        $vehicleStr = trim(($part->brand ?? '') . ' ' . ($part->model ?? '') . ' ' . ($part->year_from ?? ''));
    @endphp

    {{-- ── SMALL LABEL (2×1 inches) ───────────────────────────── --}}
    <div class="label-small">
        <div class="lbl-code">{{ $part->part_code }}</div>
        <div class="lbl-ref">{{ $part->part_code }}</div>
        <div class="lbl-vehicle">{{ $vehicleStr ?: 'Auto Zenith Parts' }}</div>
    </div>

    {{-- ── LARGE LABEL (4×6 inches — landscape 6w×4h) ─────────── --}}
    <div class="label-large">
        <div class="lbl-header">
            <div class="brand">AUTO <span>ZENITH</span> PARTS</div>
            <div class="inv-ref">{{ $part->part_code }}</div>
        </div>

        <div class="lbl-body">
            {{-- Left column: barcode --}}
            <div class="lbl-barcode-col">
                <div class="barcode-font">{{ $part->part_code }}</div>
                <div class="barcode-text">{{ $part->part_code }}</div>
                @if($part->bin_location)
                <div style="margin-top:6px;font-size:9px;color:#666;text-align:center;">
                    📦 {{ $part->bin_location }}
                </div>
                @endif
                @if($part->donor_vin)
                <div style="margin-top:4px;font-family:monospace;font-size:7px;color:#aaa;text-align:center;">
                    VIN: {{ $part->donor_vin }}
                </div>
                @endif
            </div>

            {{-- Right column: product info --}}
            <div class="lbl-info-col">
                <div class="lbl-part-name">{{ $part->part_name }}</div>

                @if($vehicleStr)
                <div class="lbl-vehicle-info">
                    <strong>Vehicle:</strong> {{ $vehicleStr }}<br>
                    @if(!empty($part->engine_code_oem))
                    <strong>Engine:</strong> {{ $part->engine_code_oem }}<br>
                    @endif
                    @if(!empty($part->transmission_code_oem))
                    <strong>Gearbox:</strong> {{ $part->transmission_code_oem }}
                    @if($part->pin_count) ({{ $part->pin_count }}-pin)@endif<br>
                    @endif
                    @if(!empty($part->part_category))
                    <strong>Category:</strong> {{ $part->part_category }}<br>
                    @endif
                    @if(!empty($part->conditions_and_options))
                    <strong>Condition Note:</strong> {{ $part->conditions_and_options }}
                    @endif
                </div>
                @endif

                <div class="lbl-grade-row">
                    @if($part->condition_grade)
                    <span class="grade-badge grade-{{ $part->condition_grade }}">Grade {{ $part->condition_grade }}</span>
                    @endif
                    @if($part->is_major_component ?? false)
                    <span class="major-badge">⚡ Major Component</span>
                    @endif
                    @if($part->legal_trace_required ?? false)
                    <span class="legal-badge">⚠ Legal Trace</span>
                    @endif
                </div>

                <div class="lbl-price" style="margin-top:auto;">
                    <div class="price-label">RETAIL PRICE</div>
                    {{ $retail }}
                    @if($wholesale)
                    <div class="trade-price">Trade: {{ $wholesale }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lbl-footer">
            <div class="lbl-location">{{ $part->location ?? 'Auto Zenith' }}</div>
            <div class="lbl-condition">{{ $part->conditions_and_options ?? ($part->description ? substr($part->description, 0, 60) : '') }}</div>
            <div class="lbl-website">autozenithparts.com</div>
        </div>
    </div>

    @endforeach
</div>

<script>
const CURRENT_SIZE = '{{ $size }}';

function setSize(size) {
    document.getElementById('btn-small').classList.toggle('active', size === 'small');
    document.getElementById('btn-large').classList.toggle('active', size === 'large');
    document.body.classList.remove('print-small', 'print-large');
    document.body.classList.add('print-' + size);
    // Update URL so page reload keeps the choice
    const url = new URL(window.location.href);
    url.searchParams.set('size', size);
    window.history.replaceState({}, '', url);
}

// Apply on load
setSize(CURRENT_SIZE || 'large');
</script>
</body>
</html>
