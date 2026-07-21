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
   ROOM LABEL — FIXED: previously 12×4in specialty stock using
   the unreliable "Libre Barcode 128" font trick (no checksum,
   and visibly overflowing its box at 200px font-size in real
   use). Now uses the exact same 90×200mm card / 3-per-A4-
   landscape-sheet format as bin labels, with the same reliable
   checksummed barcode renderer — genuinely scannable, and
   prints on standard A4 paper, no specialty stock needed.
═══════════════════════════════════════════════════════ */
.room-label-card {
    width:90mm; height:200mm;
    box-sizing:border-box;
    border:2px solid #c9a84c;
    border-radius:4mm;
    padding:8mm 4mm;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    text-align:center;
    background:#fff;
}
.room-label-card .kind { font-size:13px; font-weight:700; color:#c9a84c; text-transform:uppercase; letter-spacing:3px; margin-bottom:6mm; }
.room-label-card .code { font-size:30px; font-weight:900; color:#0d1b2a; letter-spacing:1px; margin-bottom:6mm; line-height:1.1; }
.room-label-card .name { font-size:15px; font-weight:700; color:#333; margin-top:6mm; }
.room-label-card .loc  { font-size:12px; color:#888; margin-top:2mm; text-transform:uppercase; letter-spacing:1px; }
.room-label-card svg { max-width:78mm; height:auto; }
.room-label-card .code-repeat { font-size:15px; font-weight:700; color:#333; margin-top:6mm; font-family:monospace; }
.room-label-card .brand { font-size:11px; font-weight:700; color:#c9a84c; margin-top:6mm; letter-spacing:2px; }

.section-title { font-size:13px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:2px; padding:8px 0 4px; }

@media print {
    body { background:white; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
}

/* FIXED: rooms now use the same 90×200mm A4 landscape format as
   bins (previously 12×4in specialty stock with an unreliable font-
   based fake barcode) — a single fixed @page rule works for both,
   no more dynamic per-filter page-size switching needed. */
@page { size: A4 landscape; margin: 0; }

/* Toggle visibility */
body.rooms-only .bin-sheet    { display:none; }
body.bins-only  .room-sheet   { display:none; }
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
    <span class="tip">Bins & Rooms: A4 landscape, 90×200mm ×3 per sheet — same paper for both, no need to filter or switch stock.</span>
</div>

<div class="labels-wrap">

{{-- ── ROOM LABELS — retiled to the same 90×200mm/3-per-A4-landscape
     format as bins, with the same reliable checksummed barcode ──── --}}
@if(!empty($rooms) && count($rooms) > 0)
<div class="section-title rooms-title">📦 Storage Room Labels</div>
@php $roomChunks = collect($rooms)->chunk(3); @endphp
@foreach($roomChunks as $chunk)
<div class="bin-sheet room-sheet">
    @foreach($chunk as $room)
    <div class="bin-panel-slot">
        <div class="room-label-card">
            <div class="kind">Storage Room</div>
            <div class="code">{{ $room->code ?? Str::upper(Str::limit($room->name, 8, '')) }}</div>
            <svg class="room-barcode-svg" id="rc-{{ $room->id }}"></svg>
            <div class="code-repeat">{{ $room->code ?? $room->id }}</div>
            <div class="name">{{ $room->name }}</div>
            <div class="loc">{{ $room->location ?? '' }}</div>
            <div class="brand">AUTO ZENITH PARTS</div>
        </div>
    </div>
    @endforeach
    @for ($pad = $chunk->count(); $pad < 3; $pad++)
    <div class="bin-panel-slot empty"></div>
    @endfor
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
    // FIXED: wrapped in try/catch — a single bad render (e.g. an
    // unsupported character in one bin code) could previously throw
    // and silently halt the ENTIRE script, leaving every remaining
    // bin (and room) label blank with no barcode at all. Now one
    // failure only skips that one label.
    try {
        const codeText = svg.parentElement.querySelector('.code-repeat').textContent.trim();
        renderBarcode(svg.id, codeText, { width: 2.4, height: 70 });
    } catch (e) {
        console.error('Bin barcode render failed for', svg.id, e);
    }
});

// NEW: render room barcodes with the same reliable generator —
// previously used the unreliable "Libre Barcode 128" font trick.
document.querySelectorAll('.room-barcode-svg').forEach(svg => {
    try {
        const codeText = svg.parentElement.querySelector('.code-repeat').textContent.trim();
        renderBarcode(svg.id, codeText, { width: 2.8, height: 80 });
    } catch (e) {
        console.error('Room barcode render failed for', svg.id, e);
    }
});

// FIXED: rooms now use the exact same A4 landscape format as bins,
// so the page-size switching this used to need is gone — one @page
// rule (already set earlier in the CSS) works for everything.
</script>
</body>
</html>
