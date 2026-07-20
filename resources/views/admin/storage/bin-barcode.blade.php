{{-- FILE: resources/views/admin/storage/bin-barcode.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Bin Barcode — {{ $shelf->full_bin_code }}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
    .toolbar { text-align: center; padding: 16px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }

    /* ── SHEET: A4 landscape, 3 equal 90mm-wide panels ──
       297mm wide / 3 = 99mm per panel slot, with a 90mm actual
       printable label inset from a 9mm gap so the cut line sits
       cleanly between labels rather than through printed content. */
    @page { size: A4 landscape; margin: 0; }
    .sheet {
        width: 297mm; height: 210mm;
        display: flex;
        background: #fff;
        margin: 0 auto;
    }
    .panel-slot {
        width: 99mm; height: 210mm;
        display: flex; align-items: center; justify-content: center;
        box-sizing: border-box;
        border-right: 2px dashed #000; /* deep, clear cut line between panels */
    }
    .panel-slot:last-child { border-right: none; }
    .label {
        width: 90mm; height: 200mm;
        box-sizing: border-box;
        border: 2px solid #000; /* deep border for easy cut/trim to exact size */
        border-radius: 4mm;
        padding: 8mm 4mm;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
    }
    .label .bin-code {
        font-size: 28px; font-weight: 900; color: #0A1F5C;
        margin-bottom: 10mm; letter-spacing: 1px;
        writing-mode: horizontal-tb;
    }
    .label svg { max-width: 78mm; height: auto; }
    .label .bin-code-repeat {
        font-size: 16px; font-weight: 700; color: #333;
        margin-top: 10mm; font-family: monospace;
    }

    @media print {
        .no-print { display: none !important; }
        body { margin: 0; padding: 0; background: #fff; }
    }
    @media screen {
        .sheet { box-shadow: 0 2px 16px rgba(0,0,0,0.15); margin-top: 12px; }
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button class="print-btn" onclick="window.print()">🖨 Print Label (3-up, A4 landscape)</button>
    <p style="font-size:12px;color:#666;margin-top:8px;">Prints 3 copies of this bin's label on one A4 sheet — cut along the dashed lines.</p>
  </div>

  <div class="sheet">
    @for ($i = 0; $i < 3; $i++)
    <div class="panel-slot">
        <div class="label">
            <div class="bin-code">{{ $shelf->full_bin_code }}</div>
            <svg id="barcode-{{ $i }}"></svg>
            <div class="bin-code-repeat">{{ $shelf->full_bin_code }}</div>
        </div>
    </div>
    @endfor
  </div>

  <script>
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
            const barHeight = opts.height || 70, barWidth = opts.width || 2.4;
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
            // Barcode value only (no repeated text label baked into the
            // SVG itself) — the bin code already appears large above and
            // below each barcode in the label layout.
            svg.innerHTML = bars;
        };
    })(window);

    // Render the same barcode into all 3 panel copies on this sheet.
    for (let i = 0; i < 3; i++) {
        renderBarcode("barcode-" + i, "{{ $shelf->full_bin_code }}", {
            width: 2.4, height: 70, displayValue: false,
        });
    }
  </script>
</body>
</html>
