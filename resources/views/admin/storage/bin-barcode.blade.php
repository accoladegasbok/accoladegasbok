{{-- FILE: resources/views/admin/storage/bin-barcode.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Bin Barcode — {{ $shelf->full_bin_code }}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
    .toolbar { text-align: center; padding: 16px; }
    .toolbar h3 { font-size: 13px; color: #333; margin-bottom: 8px; }
    .pos-btn { padding: 8px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; border: 2px solid #0A1F5C; background: white; color: #0A1F5C; margin: 0 4px; }
    .pos-btn.active { background: #0A1F5C; color: white; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 12px; }

    /* FIXED: matches the batch print page's actual layout — 90mm wide
       x 200mm tall cards, tiled SIDE BY SIDE (not stacked) on a
       landscape A4 sheet, 3 slots of 99mm each = 297mm total width.
       Position choice reoriented Left/Middle/Right to match this
       horizontal arrangement (was Top/Middle/Bottom for a stacked
       layout that doesn't match the batch page's design). */
    @page { size: A4 landscape; margin: 0; }
    .sheet {
        width: 297mm; height: 210mm;
        display: flex;
        background: #fff;
        margin: 0 auto;
    }
    .slot {
        width: 99mm; height: 210mm;
        display: flex; align-items: center; justify-content: center;
        box-sizing: border-box;
    }
    .label {
        width: 90mm; height: 200mm;
        box-sizing: border-box;
        border: 2px solid #000;
        border-radius: 4mm;
        padding: 8mm 4mm;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
    }
    .label .bin-code { font-size: 34px; font-weight: 900; color: #0A1F5C; letter-spacing: 1px; line-height: 1; }
    .label .room-label { font-size: 13px; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 2px; margin-top: 4mm; margin-bottom: 8mm; }
    .label svg { max-width: 78mm; height: auto; }
    .label .bin-code-repeat { font-size: 16px; font-weight: 700; color: #333; margin-top: 6mm; font-family: monospace; }

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
    <h3>Choose position on the A4 sheet — the other two slots print blank, so this sheet can take more labels later</h3>
    <button class="pos-btn active" id="btn-left" onclick="setPosition('left')">◀ Left</button>
    <button class="pos-btn" id="btn-middle" onclick="setPosition('middle')">— Middle</button>
    <button class="pos-btn" id="btn-right" onclick="setPosition('right')">▶ Right</button>
    <br>
    <button class="print-btn" onclick="window.print()">🖨 Print Label</button>
  </div>

  <div class="sheet">
    <div class="slot" id="slot-left"></div>
    <div class="slot" id="slot-middle"></div>
    <div class="slot" id="slot-right"></div>
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
            const barHeight = opts.height || 100, barWidth = opts.width || 4.4;
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

    const BIN_CODE = "{{ $shelf->full_bin_code }}";
    const ROOM_NAME = "{{ $shelf->room_name ?? '' }}";

    function labelHtml(svgId) {
        return `
            <div class="label">
                <div class="bin-code">${BIN_CODE}</div>
                ${ROOM_NAME ? `<div class="room-label">${ROOM_NAME}</div>` : ''}
                <svg id="${svgId}"></svg>
                <div class="bin-code-repeat">${BIN_CODE}</div>
            </div>`;
    }

    function setPosition(pos) {
        ['left','middle','right'].forEach(p => {
            document.getElementById('btn-' + p).classList.toggle('active', p === pos);
            document.getElementById('slot-' + p).innerHTML = '';
        });
        const svgId = 'barcode-' + pos;
        document.getElementById('slot-' + pos).innerHTML = labelHtml(svgId);
        renderBarcode(svgId, BIN_CODE, { width: 4.4, height: 100 });
    }

    setPosition('left'); // default
  </script>
</body>
</html>
