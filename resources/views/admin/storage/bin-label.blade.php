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
    background:#0d1b2a; color:white;
    padding:10px 20px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.controls h2 { font-size:13px; font-weight:700; color:#c9a84c; }
.ctrl-btn { padding:6px 14px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:none; letter-spacing:0.5px; text-transform:uppercase; }
.ctrl-btn.on  { background:#c9a84c; color:#0d1b2a; }
.ctrl-btn.off { background:#1e3a5f; color:#aaa; border:1px solid #334; }
.print-btn    { background:#1a6b3c; color:white; }
.labels-wrap  { margin-top:58px; padding:16px; display:flex; flex-direction:column; gap:12px; }

/* FIXED: flipped from landscape/3-side-by-side to PORTRAIT/3-stacked
   bands, matching the single-bin barcode page's confirmed design.
   Cards are now 200mm wide x 90mm tall (was 90x200mm) to fit properly
   within each 99mm-tall stacked band on a portrait A4 sheet. Applies
   to BOTH bin and room labels — same paper, same card shape, for
   real consistency across every label type in the app. */
@page { size: A4 portrait; margin: 0; }

.bin-sheet {
    width:210mm; height:297mm;
    display:flex; flex-direction:column;
    background:#fff;
    page-break-after:always;
}
.bin-panel-slot {
    width:210mm; height:99mm;
    display:flex; align-items:center; justify-content:center;
    box-sizing:border-box;
}
.bin-label, .room-label-card {
    width:200mm; height:90mm;
    box-sizing:border-box;
    border:2px solid #000;
    border-radius:4mm;
    padding:6mm 10mm;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    text-align:center;
    background:#fff;
}
.room-label-card { border-color:#c9a84c; }
.bin-label .code { font-size:41px; font-weight:900; color:#0A1F5C; letter-spacing:1px; line-height:1; }
.room-label-card .code { font-size:50px; font-weight:900; color:#0A1F5C; letter-spacing:1px; line-height:1; }
.bin-label .room { font-size:16px; font-weight:700; color:#666; text-transform:uppercase; letter-spacing:2px; margin-top:4mm; margin-bottom:8mm; }
.room-label-card .kind { font-size:22px; font-weight:700; color:#c9a84c; text-transform:uppercase; letter-spacing:3px; margin-bottom:3mm; }
.bin-label svg, .room-label-card svg { max-width:170mm; height:auto; }
.bin-label .code-repeat, .room-label-card .code-repeat { font-size:19px; font-weight:700; color:#333; margin-top:3mm; font-family:monospace; }
.room-label-card .name { font-size:17px; font-weight:700; color:#333; margin-top:3mm; }
.room-label-card .loc  { font-size:13px; color:#888; margin-top:1mm; text-transform:uppercase; letter-spacing:1px; }
.room-label-card .brand { font-size:12px; font-weight:700; color:#c9a84c; margin-top:2mm; letter-spacing:2px; }

.section-title { font-size:13px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:2px; padding:8px 0 4px; }

@media print {
    body { background:white; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
}

/* Toggle visibility */
/* FIXED: room sheets share the .bin-sheet class (for reused styling),
   so "body.rooms-only .bin-sheet { display:none }" was hiding room
   sheets too, since they also carry that class — meaning "Rooms Only"
   hid EVERYTHING, including the rooms themselves. Selectors now
   distinguish properly: .bin-sheet.room-sheet for actual room sheets,
   .bin-sheet:not(.room-sheet) for actual bin sheets. */
body.rooms-only .bin-sheet:not(.room-sheet) { display:none; }
body.bins-only  .bin-sheet.room-sheet       { display:none; }
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
    <span class="tip">Bins & Rooms: A4 portrait, 200×90mm ×3 stacked per sheet — same paper for both.</span>
</div>

<div class="labels-wrap">

{{-- ── ROOM LABELS — 200×90mm, 3 stacked bands per A4 portrait sheet ── --}}
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
    <div class="bin-panel-slot"></div>
    @endfor
</div>
@endforeach
@endif

{{-- ── BIN LABELS — 200×90mm, 3 stacked bands per A4 portrait sheet ── --}}
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
    @for ($pad = $chunk->count(); $pad < 3; $pad++)
    <div class="bin-panel-slot"></div>
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

// ── Checksummed Code128 SVG barcode — same reliable generator used
// elsewhere in the app, replacing the "Libre Barcode 128" Google Font
// this used to rely on for room labels specifically. ──
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
    try {
        const codeText = svg.parentElement.querySelector('.code-repeat').textContent.trim();
        renderBarcode(svg.id, codeText, { width: 2.9, height: 84 });
    } catch (e) {
        console.error('Bin barcode render failed for', svg.id, e);
    }
});

document.querySelectorAll('.room-barcode-svg').forEach(svg => {
    try {
        const codeText = svg.parentElement.querySelector('.code-repeat').textContent.trim();
        renderBarcode(svg.id, codeText, { width: 3.9, height: 110 });
    } catch (e) {
        console.error('Room barcode render failed for', svg.id, e);
    }
});
</script>
</body>
</html>
