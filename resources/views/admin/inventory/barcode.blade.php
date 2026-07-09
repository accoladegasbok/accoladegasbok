{{-- FILE: resources/views/admin/inventory/barcode.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Barcode — {{ $part->part_code }}</title>
  <script>
    // Self-contained CODE128 generator — no external CDN dependency,
    // so labels still print correctly even with no internet access
    // at the warehouse printer.
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
            const barHeight = opts.height || 50, barWidth = opts.width || 2;
            const showText = opts.displayValue !== false, fontSize = opts.fontSize || 14;
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
            const totalWidth = x + 10, totalHeight = barHeight + (showText ? fontSize + 10 : 5);
            svg.setAttribute('viewBox', `0 0 ${totalWidth} ${totalHeight}`);
            svg.setAttribute('width', totalWidth);
            svg.setAttribute('height', totalHeight);
            svg.innerHTML = bars + (showText ? `<text x="${totalWidth/2}" y="${barHeight+fontSize+2}" font-family="monospace" font-size="${fontSize}" text-anchor="middle" fill="#000">${text}</text>` : '');
        };
    })(window);
  </script>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
    .toolbar { text-align: center; margin-bottom: 16px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .toggle-btn {
      background: #fff; color: #0A1F5C; border: 2px solid #0A1F5C; padding: 9px 20px; border-radius: 8px;
      font-weight: bold; cursor: pointer; font-size: 13px; transition: all .15s;
    }
    .toggle-btn.active { background: #0A1F5C; color: #fff; }

    /* ── 2x3 inch label — matches standard parts-label stock.
         Physical print size enforced at @page level so the printer's
         page size matches the label exactly instead of printing a
         small label onto a full sheet with big margins. ── */
    .label {
      width: 2in; height: 3in; box-sizing: border-box;
      background: #fff; border: 1px solid #ccc; border-radius: 6px;
      padding: 8px 8px; margin: 0 auto;
      overflow: hidden;
      display: flex; flex-direction: column;
    }

    /* ══ BARCODE-ONLY MODE — unchanged from before, just the code ══ */
    .label.barcode-only { align-items: center; justify-content: center; text-align: center; gap: 6px; }
    .label.barcode-only .info-mode { display: none; }
    .label:not(.barcode-only) .barcode-only-mode { display: none; }
    .label.barcode-only .part-code-only { font-size: 11px; font-weight: bold; color: #0A1F5C; margin-top: 4px; }

    /* ══ WITH-INFO MODE — dense pull-tag style, inspired by the
         standard yard "pull tag" format (GUID barcode top, key:value
         field rows, compatibility block, larger scan barcode at
         bottom). Adapted to what this system actually tracks, plus
         compatibility data (year range / trims / drive / pin count)
         which a standard pull tag doesn't carry but customers see on
         the storefront (#10/#11), so it's useful for staff to have
         on the physical label too. ══ */
    .info-mode { display: flex; flex-direction: column; height: 100%; }
    .info-mode .top-barcode { text-align: center; margin-bottom: 2px; }
    .info-mode .top-barcode svg { max-width: 100%; height: auto; }
    .info-mode .guid-label { font-size: 7px; color: #999; text-align: center; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }

    .field-grid { font-size: 8.5px; line-height: 1.35; flex: 1; }
    .field-row { display: flex; justify-content: space-between; border-bottom: 1px dotted #ddd; padding: 1px 0; }
    .field-row .k { color: #666; font-weight: 600; flex-shrink: 0; }
    .field-row .v { color: #0A1F5C; font-weight: 700; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 68%; }

    .compat-block { margin-top: 3px; padding-top: 3px; border-top: 1px solid #C8960C; }
    .compat-title { font-size: 7px; font-weight: 800; color: #C8960C; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1px; }
    .compat-line { font-size: 7.5px; color: #333; line-height: 1.3; }

    .price-strip { text-align: center; font-size: 14px; font-weight: 800; color: #0A1F5C; margin: 3px 0; }

    .bottom-barcode { text-align: center; margin-top: auto; }
    .bottom-barcode svg { max-width: 100%; height: auto; }
    .wo-label { font-size: 7px; color: #999; text-align: center; letter-spacing: 1px; text-transform: uppercase; }

    @media print {
      .no-print { display: none; }
      body { margin: 0; padding: 0; background: #fff; }
      @page { size: 2in 3in; margin: 0; }
      .label { box-shadow: none; border: none; page-break-after: always; }
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button id="btnWithInfo" class="toggle-btn active" onclick="setMode(false)">📄 With Product Info</button>
    <button id="btnBarcodeOnly" class="toggle-btn" onclick="setMode(true)">🏷 Barcode Only</button>
    <button class="print-btn" onclick="window.print()">🖨 Print Label (2×3")</button>
  </div>

  @php
    $priceLocal = $part->price_local ?? $part->price_usd;
    $sym = match($part->currency_code ?? 'USD') { 'NGN' => '₦', 'GHS' => 'GH₵', default => '$' };
    $priceFmt = $sym . (($part->currency_code ?? 'USD') === 'NGN' ? number_format($priceLocal) : number_format($priceLocal, 2));

    $compatFrom = $part->compat_year_from ?? $part->year_from;
    $compatTo   = $part->compat_year_to   ?? $part->year_to;
    $hasCompatRange = $compatFrom && $compatTo && ($compatFrom != $part->year_from || $compatTo != $part->year_to || $compatFrom != $compatTo);
  @endphp

  <div class="label" id="labelBox">

    {{-- ══ BARCODE-ONLY MODE ══ --}}
    <div class="barcode-only-mode" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; gap:6px;">
        <svg id="barcode-solo"></svg>
        <div class="part-code-only">{{ $part->part_code }}</div>
    </div>

    {{-- ══ WITH-INFO MODE — pull-tag style ══ --}}
    <div class="info-mode">

        <div class="guid-label">GUID</div>
        <div class="top-barcode"><svg id="barcode-top"></svg></div>

        <div class="field-grid">
            <div class="field-row"><span class="k">GRADE:</span><span class="v">{{ $part->condition_grade }}</span></div>
            <div class="field-row"><span class="k">TYPE:</span><span class="v">{{ $part->part_category }}</span></div>
            <div class="field-row"><span class="k">YEAR:</span><span class="v">{{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif</span></div>
            <div class="field-row"><span class="k">MAKE:</span><span class="v">{{ strtoupper($part->brand) }}</span></div>
            <div class="field-row"><span class="k">MODEL:</span><span class="v">{{ strtoupper($part->model) }}</span></div>
            <div class="field-row"><span class="k">PART:</span><span class="v">{{ $part->part_name }}</span></div>
            @if($part->side && $part->side !== 'N/A')
            <div class="field-row"><span class="k">SIDE:</span><span class="v">{{ $part->side }}</span></div>
            @endif
            <div class="field-row"><span class="k">STOCK#:</span><span class="v">{{ $part->part_code }}</span></div>
            @if(!empty($part->source_ref))
            <div class="field-row"><span class="k">SRC REF:</span><span class="v">{{ $part->source_ref }}</span></div>
            @endif
            @if($part->donor_vin)
            <div class="field-row"><span class="k">VIN#:</span><span class="v" style="font-size:7.5px;">{{ $part->donor_vin }}</span></div>
            @endif
            @if($part->engine_code_oem)
            <div class="field-row"><span class="k">ENGINE:</span><span class="v">{{ $part->engine_code_oem }}</span></div>
            @endif
            @if($part->transmission_code_oem)
            <div class="field-row"><span class="k">GEARBOX:</span><span class="v">{{ $part->transmission_code_oem }}@if($part->pin_count) ({{ $part->pin_count }}-pin)@endif</span></div>
            @endif
            @if($part->drive_type)
            <div class="field-row"><span class="k">DRIVE:</span><span class="v">{{ $part->drive_type }}</span></div>
            @endif
        </div>

        {{-- Compatibility block — this is the improvement over the
             standard pull-tag format, which has no compatibility
             concept at all. Only shown when there's actually a wider
             range or trim/fitment info beyond the donor year alone. --}}
        @if($hasCompatRange || $part->compatible_trims || $part->not_compatible_note)
        <div class="compat-block">
            <div class="compat-title">✓ Also Fits</div>
            @if($hasCompatRange)
            <div class="compat-line">{{ strtoupper($part->brand) }} {{ strtoupper($part->model) }} {{ $compatFrom }}–{{ $compatTo }}</div>
            @endif
            @if($part->compatible_trims)
            <div class="compat-line">Trims: {{ $part->compatible_trims }}</div>
            @endif
            @if($part->not_compatible_note)
            <div class="compat-line" style="color:#a32d2d;">✕ Not: {{ $part->not_compatible_note }}</div>
            @endif
        </div>
        @endif

        <div class="price-strip">{{ $priceFmt }}</div>

        <div class="wo-label">SCAN</div>
        <div class="bottom-barcode"><svg id="barcode-bottom"></svg></div>
    </div>

  </div>

  <script>
    const PART_CODE = "{{ $part->part_code }}";

    function setMode(barcodeOnly) {
        const label = document.getElementById('labelBox');
        label.classList.toggle('barcode-only', barcodeOnly);
        document.getElementById('btnWithInfo').classList.toggle('active', !barcodeOnly);
        document.getElementById('btnBarcodeOnly').classList.toggle('active', barcodeOnly);

        const url = new URL(window.location.href);
        url.searchParams.set('info', barcodeOnly ? '0' : '1');
        window.history.replaceState({}, '', url.toString());

        renderAllBarcodes(barcodeOnly);
    }

    function renderAllBarcodes(barcodeOnly) {
        if (barcodeOnly) {
            renderBarcode("barcode-solo", PART_CODE, { width: 2.2, height: 60, displayValue: true, fontSize: 13 });
        } else {
            // Top = small GUID-style reference code, Bottom = larger
            // primary scan code — same value printed twice at two
            // sizes, mirroring the standard pull-tag layout where the
            // GUID (top) and WO# (bottom) are the same number, just
            // sized differently for close reference vs quick scanning.
            renderBarcode("barcode-top",    PART_CODE, { width: 1.1, height: 22, displayValue: false });
            renderBarcode("barcode-bottom", PART_CODE, { width: 1.6, height: 34, displayValue: true, fontSize: 10 });
        }
    }

    const initialParams = new URLSearchParams(window.location.search);
    const startBarcodeOnly = initialParams.get('info') === '0';
    if (startBarcodeOnly) {
        document.getElementById('labelBox').classList.add('barcode-only');
        document.getElementById('btnWithInfo').classList.remove('active');
        document.getElementById('btnBarcodeOnly').classList.add('active');
    }
    renderAllBarcodes(startBarcodeOnly);
  </script>
</body>
</html>
