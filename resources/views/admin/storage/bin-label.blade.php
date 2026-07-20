<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bin & Room Labels</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',Arial,sans-serif; background:#eee; }

.controls {
    position:fixed; top:0; left:0; right:0; z-index:999;
    background:#0d1b2a; padding:10px 20px;
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.controls h2 { font-size:13px; font-weight:700; color:#c9a84c; }
.ctrl-btn { padding:6px 16px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:none; text-transform:uppercase; }
.ctrl-btn.on  { background:#c9a84c; color:#0d1b2a; }
.ctrl-btn.off { background:#1e3a5f; color:#aaa; border:1px solid #334; }
.print-btn { background:#1a6b3c; color:white; }
.tip  { color:#555; font-size:10px; margin-left:auto; }

.labels-wrap { margin-top:58px; padding:16px; display:flex; flex-direction:column; gap:12px; }

/* ═══════════════════════════════════════════════════════
   BIN LABELS — 90mm × 200mm actual label size, 3 panels
   tiled per A4 landscape sheet (297mm/3 = 99mm slots with
   a small gap so the cut line falls between labels, not
   through printed content). Retiled from the old single
   12×4in-per-page format — same shelves data, new layout,
   and swapped from the unreliable font-rendered barcode to
   a properly checksummed Code128 SVG (same generator used
   on the single-bin quick-print page) for real scan
   reliability with a physical scanner gun.
═══════════════════════════════════════════════════════ */
.bin-sheet {
    width:297mm; height:210mm;
    display:flex;
    background:#fff;
    page-break-after:always;
}
.bin-panel-slot {
    width:99mm; height:210mm;
    display:flex; align-items:center; justify-content:center;
    box-sizing:border-box;
    border-right:2px dashed #000;
}
.bin-panel-slot:last-child { border-right:none; }
.bin-panel-slot.empty { border-right:2px dashed #ccc; }
.bin-label {
    width:90mm; height:200mm;
    box-sizing:border-box;
    border:2px solid #000;
    border-radius:4mm;
    padding:8mm 4mm;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    text-align:center;
    background:#fff;
}
.bin-label .code { font-size:26px; font-weight:900; color:#0A1F5C; letter-spacing:1px; margin-bottom:10mm; }
.bin-label .room { font-size:13px; font-weight:700; color:#666; text-transform:uppercase; letter-spacing:2px; margin-bottom:6mm; }
.bin-label svg { max-width:78mm; height:auto; }
.bin-label .code-repeat { font-size:15px; font-weight:700; color:#333; margin-top:10mm; font-family:monospace; }

/* ═══════════════════════════════════════════════════════
   ROOM LABEL — 12×4 landscape (unchanged — room/door
   signage is a different physical use case from a small
   bin tag, kept at its original larger format)
═══════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Inter:wght@700;900&display=swap');
.label-room {
    width:12in; height:4in;
    background:white; border:4px solid #c9a84c;
    display:flex; align-items:stretch;
    overflow:hidden; page-break-inside:avoid; page-break-after:always;
}
.room-code-block {
    width:4.2in; background:#c9a84c;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:16px; flex-shrink:0;
}
.room-code-block .room-label { font-size:22px; font-weight:900; color:#0d1b2a; letter-spacing:3px; text-transform:uppercase; text-align:center; margin-bottom:12px; }
.room-code-block .room-code  { font-size:120px; font-weight:900; color:#0d1b2a; letter-spacing:-4px; line-height:0.9; text-align:center; }
.room-code-block .room-loc   { font-size:16px; font-weight:700; color:#0d1b2a; opacity:0.7; margin-top:12px; text-align:center; letter-spacing:2px; }
.room-barcode-block { flex:1; background:#fafafa; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:10px 16px; border-left:4px solid #c9a84c; }
.room-barcode-block .bc { font-family:'Libre Barcode 128',monospace; font-size:200px; line-height:1; color:#000; text-align:center; width:100%; white-space:nowrap; overflow:hidden; }
.room-barcode-block .bc-text { font-size:26px; font-weight:900; letter-spacing:6px; color:#333; margin-top:8px; font-family:monospace; text-align:center; }
.room-barcode-block .brand   { font-size:14px; font-weight:700; color:#c9a84c; margin-top:16px; letter-spacing:2px; }

.section-title { font-size:13px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:2px; padding:8px 0 4px; }

@media print {
    body { background:white; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
}

/* Dynamic @page size — set by JS based on active filter, since a
   single print job can't cleanly mix A4 (bins) and 12x4in (rooms)
   paper stock. Defaults to A4 landscape (the common case). */
@page { size: A4 landscape; margin: 0; }

/* Toggle visibility */
body.rooms-only .bin-sheet    { display:none; }
body.bins-only  .label-room   { display:none; }
body.rooms-only .bins-title   { display:none; }
body.bins-only  .rooms-title  { display:none; }
</style>
</head>
<body id="labelBody">

<div class="controls">
    <h2>🏷 Labels — {{ count($rooms ?? []) }} room(s) · {{ count($shelves ?? []) }} bin(s)</h2>
    <button class="ctrl-btn on" onclick="setFilter('all')"   id="btn-all">All</button>
    <button class="ctrl-btn off" onclick="setFilter('rooms')" id="btn-rooms">Rooms Only</button>
    <button class="ctrl-btn off" onclick="setFilter('bins')"  id="btn-bins">Bins Only</button>
    <span style="color:#555;">|</span>
    <button class="ctrl-btn print-btn" onclick="window.print()">🖨 Print</button>
    <a href="javascript:history.back()" style="color:#aaa;font-size:11px;text-decoration:none;">← Back</a>
    <span class="tip">Bins: A4 landscape, 90×200mm ×3 per sheet. Rooms: 12×4in label stock. Filter to one type before printing if mixing both — different paper.</span>
</div>

<div class="labels-wrap">

{{-- ── ROOM LABELS (unchanged, 12×4in) ─────────────────────────── --}}
@if(!empty($rooms) && count($rooms) > 0)
<div class="section-title rooms-title">📦 Storage Room Labels</div>
@foreach($rooms as $room)
<div class="label-room">
    <div class="room-code-block">
        <div class="room-label">Storage Room</div>
        <div class="room-code">{{ $room->code ?? Str::upper(Str::limit($room->name, 4, '')) }}</div>
        <div class="room-loc">{{ $room->location ?? '' }}</div>
    </div>
    <div class="room-barcode-block">
        <div class="bc">ROOM-{{ $room->code ?? $room->id }}</div>
        <div class="bc-text">ROOM-{{ $room->code ?? $room->id }}</div>
        <div style="font-size:18px;font-weight:700;color:#333;margin-top:12px;text-align:center;">{{ $room->name }}</div>
        <div class="brand">AUTO ZENITH PARTS</div>
    </div>
</div>
@endforeach
@endif

{{-- ── BIN LABELS — retiled to 90×200mm, 3 per A4 landscape sheet ── --}}
@if(!empty($shelves) && count($shelves) > 0)
<div class="section-title bins-title">🗄 Bin Labels</div>
@php $shelfChunks = collect($shelves)->chunk(3); @endphp
@foreach($shelfChunks as $chunk)
<div class="bin-sheet">
    @foreach($chunk as $shelf)
    <div class="bin-panel-slot">
        <div class="bin-label">
            <div class="room">{{ $shelf->room_name ?? '' }}</div>
            <div class="code">{{ $shelf->bin_code ?? $shelf->full_bin_code }}</div>
            <svg class="bin-barcode-svg" id="bc-{{ $shelf->id }}"></svg>
            <div class="code-repeat">{{ $shelf->full_bin_code }}</div>
        </div>
    </div>
    @endforeach
    {{-- Pad the last sheet with empty slots so the sheet always tiles
         to exactly 3, keeping the dashed cut-lines consistent even
         when the final chunk has fewer than 3 bins. --}}
    @for ($pad = $chunk->count(); $pad < 3; $pad++)
    <div class="bin-panel-slot empty"></div>
    @endfor
</div>
@endforeach
@endif

</div>

<script>
function setFilter(f) {
    ['all','rooms','bins'].forEach(k => {
        document.getElementById('btn-'+k).className = 'ctrl-btn ' + (k===f?'on':'off');
    });
    document.body.className = f === 'rooms' ? 'rooms-only' : (f === 'bins' ? 'bins-only' : '');
}

// ── Checksummed Code128 SVG barcode — same generator used on the
// single-bin quick-print page, for consistent, reliable scanning. ──
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

document.querySelectorAll('.bin-barcode-svg').forEach(svg => {
    const code = svg.id.replace('bc-', '');
    // The SVG id only carries the shelf id, not the bin code text —
    // read it back from the adjacent .code-repeat element so the
    // barcode always encodes exactly what's printed on the label.
    const codeText = svg.parentElement.querySelector('.code-repeat').textContent.trim();
    renderBarcode(svg.id, codeText, { width: 2.4, height: 70 });
});

// Switch @page size dynamically based on filter, since bins (A4) and
// rooms (12x4in) need different physical paper.
const pageStyle = document.createElement('style');
document.head.appendChild(pageStyle);
function updatePageSize(f) {
    pageStyle.textContent = f === 'rooms'
        ? '@page { size: 12in 4in landscape; margin: 0; }'
        : '@page { size: A4 landscape; margin: 0; }';
}
const origSetFilter = setFilter;
setFilter = function(f) { origSetFilter(f); updatePageSize(f); };
updatePageSize('all');
</script>
</body>
</html>
