{{-- FILE: resources/views/partials/_home-finder.blade.php
     Home page "Find your part" bar: year, make, model, then the parts search.
     Included from home.blade.php. Uses the same /parts/models endpoint as the search page. --}}
@php
    $__makes = \App\Data\VehicleDatabase::makes();
    $__years = \App\Data\VehicleDatabase::years();
@endphp
<style>
  .hf-wrap{ position:relative; z-index:5; margin-top:-34px; padding:0 24px; }
  .hf{ max-width:1080px; margin:0 auto; background:#fff; border:1.5px solid var(--navy-deep); border-radius:16px; padding:22px 22px 20px; box-shadow:8px 10px 0 rgba(10,31,92,0.09); }
  .hf-title{ font-family:'Big Shoulders Display',sans-serif; text-transform:uppercase; font-weight:800; font-size:22px; color:var(--navy-deep); letter-spacing:.02em; }
  .hf-sub{ font-size:13px; color:var(--steel); margin:2px 0 14px; }
  .hf-row{ display:grid; grid-template-columns:110px 1fr 1fr 1.2fr auto; gap:10px; align-items:end; }
  .hf label{ display:block; font-family:'IBM Plex Mono',monospace; font-size:10.5px; letter-spacing:.1em; text-transform:uppercase; color:#7a8294; margin-bottom:4px; }
  .hf select, .hf input[type=text]{ width:100%; border:1.5px solid var(--line); border-radius:9px; padding:11px 12px; font:500 14px 'IBM Plex Sans',sans-serif; background:#fff; color:var(--ink); }
  .hf select:focus, .hf input:focus{ outline:3px solid rgba(200,150,12,.35); border-color:var(--gold); }
  .hf-btn{ background:var(--gold); color:var(--navy-deep); border:0; border-radius:9px; padding:12px 22px; font:700 14px 'IBM Plex Sans',sans-serif; cursor:pointer; box-shadow:0 4px 0 #9C7509; white-space:nowrap; }
  .hf-btn:hover{ transform:translateY(-1px); }
  .hf-alt{ display:flex; flex-wrap:wrap; gap:6px 18px; margin-top:12px; font-size:12.5px; color:var(--steel); }
  .hf-alt a{ color:var(--navy); font-weight:600; text-decoration:underline; text-underline-offset:3px; }
  @media (max-width:920px){ .hf-row{ grid-template-columns:1fr 1fr; } .hf-row .hf-go{ grid-column:1 / -1; } .hf-btn{ width:100%; } }
</style>

<div class="hf-wrap">
  <form class="hf" method="GET" action="{{ route('parts.search') }}" id="hfForm" role="search" aria-label="Find parts for your vehicle">
    <div class="hf-title">Find parts for your vehicle</div>
    <div class="hf-sub">Choose your car and we show what is in stock, with the engine, gearbox and drive details you need to confirm the fit.</div>
    <div class="hf-row">
      <div>
        <label for="hfYear">Year</label>
        <select name="year" id="hfYear">
          <option value="">Any</option>
          @foreach($__years as $__y)<option value="{{ $__y }}">{{ $__y }}</option>@endforeach
        </select>
      </div>
      <div>
        <label for="hfMake">Make</label>
        <select name="make" id="hfMake">
          <option value="">Any make</option>
          @foreach($__makes as $__m)<option value="{{ $__m }}">{{ $__m }}</option>@endforeach
        </select>
      </div>
      <div>
        <label for="hfModel">Model</label>
        <select name="model" id="hfModel" disabled><option value="">Pick a make first</option></select>
      </div>
      <div>
        <label for="hfQ">Part (optional)</label>
        <input type="text" name="q" id="hfQ" placeholder="engine, gearbox, door…" autocomplete="off">
      </div>
      <div class="hf-go"><button type="submit" class="hf-btn">Search parts →</button></div>
    </div>
    <div class="hf-alt">
      <span>Have a VIN? <a href="{{ url('/parts/compatibility') }}">Check by VIN</a></span>
      <span>Looking by group? <a href="{{ route('parts.categories') }}">Browse all part groups</a></span>
      <span>Can't find it? <a href="#contact">Ask us to source it</a></span>
    </div>
  </form>
</div>

<script>
(function () {
  var make = document.getElementById('hfMake'), model = document.getElementById('hfModel');
  if (!make || !model) return;
  make.addEventListener('change', function () {
    model.innerHTML = '<option value="">' + (make.value ? 'Loading…' : 'Pick a make first') + '</option>';
    model.disabled = true;
    if (!make.value) return;
    fetch(@json(route('parts.models')) + '?make=' + encodeURIComponent(make.value), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var opts = '<option value="">Any model</option>';
        (d.models || []).forEach(function (m) { opts += '<option value="' + String(m).replace(/"/g, '&quot;') + '">' + m + '</option>'; });
        model.innerHTML = opts; model.disabled = false;
      })
      .catch(function () { model.innerHTML = '<option value="">Any model</option>'; model.disabled = false; });
  });
  // Do not send empty fields: keeps the results page address clean.
  document.getElementById('hfForm').addEventListener('submit', function () {
    Array.prototype.forEach.call(this.elements, function (el) { if (el.name && !el.value) el.disabled = true; });
  });
})();
</script>
