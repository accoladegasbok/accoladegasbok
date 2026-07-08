<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bin Labels</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Inter:wght@900&display=swap');

* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',Arial,sans-serif; background:#eee; }

.controls {
    position:fixed; top:0; left:0; right:0; z-index:999;
    background:#0d1b2a; padding:10px 20px;
    display:flex; align-items:center; gap:12px;
}
.controls h2 { font-size:13px; font-weight:700; color:#c9a84c; }
.print-btn { padding:6px 16px; background:#1a6b3c; color:white; border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; text-transform:uppercase; }
.back { color:#aaa; font-size:11px; text-decoration:none; }
.tip  { color:#555; font-size:10px; margin-left:auto; }

.labels-wrap { margin-top:58px; padding:16px; display:flex; flex-direction:column; gap:16px; }

/* ── 12×4 landscape bin label ── */
.label-bin {
    width:12in;
    height:4in;
    background:white;
    border:4px solid #000;
    display:flex;
    align-items:stretch;
    overflow:hidden;
    page-break-inside:avoid;
}

/* Left — huge alphanumeric bin code */
.bin-code-block {
    width:4.2in;
    background:#0d1b2a;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:16px;
    flex-shrink:0;
}
.bin-code-block .code {
    font-size:160px;
    font-weight:900;
    color:#c9a84c;
    letter-spacing:-6px;
    line-height:0.9;
    text-align:center;
}
.bin-code-block .room {
    font-size:20px;
    font-weight:900;
    color:rgba(255,255,255,0.6);
    letter-spacing:4px;
    text-transform:uppercase;
    margin-top:12px;
    text-align:center;
}

/* Right — barcode fills remaining space */
.bin-barcode-block {
    flex:1;
    background:white;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:10px 16px;
    border-left:4px solid #000;
}
.bin-barcode-block .bc {
    font-family:'Libre Barcode 128',monospace;
    /* Fill full width, scale with container */
    font-size:200px;
    line-height:1;
    color:#000;
    text-align:center;
    width:100%;
    /* Prevent text wrap */
    white-space:nowrap;
    overflow:hidden;
}
.bin-barcode-block .bc-text {
    font-size:26px;
    font-weight:900;
    letter-spacing:6px;
    color:#000;
    margin-top:8px;
    font-family:monospace;
    text-align:center;
}

@media print {
    body { background:white; }
    .controls { display:none !important; }
    .labels-wrap { margin:0; padding:0; gap:0; }
    .label-bin { border-radius:0; page-break-after:always; border:3px solid #000; }
    @page { size:12in 4in landscape; margin:0; }
}
</style>
</head>
<body>

<div class="controls">
    <h2>📦 Bin Labels — {{ count($shelves) }}</h2>
    <button class="print-btn" onclick="window.print()">🖨 Print All</button>
    <a href="javascript:history.back()" class="back">← Back</a>
    <span class="tip">Paper: 12×4 in landscape · Disable all margins in print dialog</span>
</div>

<div class="labels-wrap">
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
</div>

</body>
</html>
