<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bin & Room Labels</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Inter:wght@700;900&display=swap');
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
   BIN LABEL — 12×4 landscape (half A4 landscape)
   Each bin column
═══════════════════════════════════════════════════════ */
.label-bin {
    width:12in; height:4in;
    background:white; border:4px solid #000;
    display:flex; align-items:stretch;
    overflow:hidden; page-break-inside:avoid;
}
.bin-code-block {
    width:4.2in; background:#0d1b2a;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:16px; flex-shrink:0;
}
.bin-code-block .code { font-size:160px; font-weight:900; color:#c9a84c; letter-spacing:-6px; line-height:0.9; text-align:center; }
.bin-code-block .room { font-size:20px; font-weight:900; color:rgba(255,255,255,0.6); letter-spacing:4px; text-transform:uppercase; margin-top:12px; text-align:center; }
.bin-barcode-block { flex:1; background:white; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:10px 16px; border-left:4px solid #000; }
.bin-barcode-block .bc { font-family:'Libre Barcode 128',monospace; font-size:200px; line-height:1; color:#000; text-align:center; width:100%; white-space:nowrap; overflow:hidden; }
.bin-barcode-block .bc-text { font-size:26px; font-weight:900; letter-spacing:6px; color:#000; margin-top:8px; font-family:monospace; text-align:center; }

/* ═══════════════════════════════════════════════════════
   ROOM LABEL — 12×4 landscape
   Darker style to distinguish from bin labels
═══════════════════════════════════════════════════════ */
.label-room {
    width:12in; height:4in;
    background:white; border:4px solid #c9a84c;
    display:flex; align-items:stretch;
    overflow:hidden; page-break-inside:avoid;
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
    .label-bin, .label-room { border-radius:0; page-break-after:always; }
    @page { size:12in 4in landscape; margin:0; }
}

/* Toggle visibility */
body.rooms-only .label-bin  { display:none; }
body.bins-only  .label-room { display:none; }
body.rooms-only .bins-title { display:none; }
body.bins-only  .rooms-title { display:none; }
</style>
</head>
<body id="labelBody">

<div class="controls">
    <h2>🏷 Labels — {{ count($rooms ?? []) }} room(s) · {{ count($shelves ?? []) }} bin(s)</h2>
    <button class="ctrl-btn on" onclick="setFilter('all')"   id="btn-all">All</button>
    <button class="ctrl-btn off" onclick="setFilter('rooms')" id="btn-rooms">Rooms Only</button>
    <button class="ctrl-btn off" onclick="setFilter('bins')"  id="btn-bins">Bins Only</button>
    <span style="color:#555;">|</span>
    <button class="ctrl-btn print-btn" onclick="window.print()">🖨 Print All</button>
    <a href="javascript:history.back()" style="color:#aaa;font-size:11px;text-decoration:none;">← Back</a>
    <span class="tip">Paper: 12×4 in landscape · Disable all margins in print dialog</span>
</div>

<div class="labels-wrap">

{{-- ── ROOM LABELS ──────────────────────────────────────────── --}}
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

{{-- ── BIN LABELS ───────────────────────────────────────────── --}}
@if(!empty($shelves) && count($shelves) > 0)
<div class="section-title bins-title">🗄 Bin Column Labels</div>
@foreach($shelves as $shelf)
<div class="label-bin">
    <div class="bin-code-block">
        <div class="code">{{ $shelf->bin_code ?? $shelf->full_bin_code }}</div>
        <div class="room">{{ $shelf->room_name ?? '' }}</div>
    </div>
    <div class="bin-barcode-block">
        <div class="bc">{{ $shelf->full_bin_code }}</div>
        <div class="bc-text">{{ $shelf->full_bin_code }}</div>
    </div>
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
</script>
</body>
</html>
