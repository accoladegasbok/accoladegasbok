{{-- FILE: resources/views/admin/inventory/barcode.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Barcode — {{ $part->part_code }}</title>
  <script>
    // Self-contained CODE128 generator — see barcode128.js comment header
    // for why this replaced the CDN-based JsBarcode library.
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
    .toolbar { text-align: center; margin-bottom: 16px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .label {
      width: 280px; background: #fff; border: 1px solid #ccc; border-radius: 6px;
      padding: 12px; margin: 0 auto; text-align: center;
    }
    .label .part-name { font-size: 12px; font-weight: bold; color: #0A1F5C; margin-bottom: 2px; }
    .label .part-sub { font-size: 10px; color: #888; margin-bottom: 8px; }
    .label .price { font-size: 16px; font-weight: bold; color: #0A1F5C; margin-top: 6px; }
    @media print { .no-print { display: none; } body { margin: 0; padding: 0; background: #fff; } }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button class="print-btn" onclick="window.print()">🖨 Print Label</button>
  </div>

  <div class="label">
    <div class="part-name">{{ $part->part_name }}</div>
    <div class="part-sub">{{ $part->brand }} {{ $part->model }} {{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif · Grade {{ $part->condition_grade }}</div>
    <svg id="barcode"></svg>
    <div class="price">
        @php
            $priceLocal = $part->price_local ?? $part->price_usd;
            $sym = match($part->currency_code ?? 'USD') { 'NGN' => '₦', 'GHS' => 'GH₵', default => '$' };
        @endphp
        {{ $sym }}{{ ($part->currency_code ?? 'USD') === 'NGN' ? number_format($priceLocal) : number_format($priceLocal, 2) }}
    </div>
  </div>

  <script>
    renderBarcode("barcode", "{{ $part->part_code }}", {
        width: 2,
        height: 50,
        displayValue: true,
        fontSize: 14,
    });
  </script>
</body>
</html>
