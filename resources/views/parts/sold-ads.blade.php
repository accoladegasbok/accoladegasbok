{{-- FILE: resources/views/parts/_sold-ads.blade.php
     "Recently sold" strip under the search results: parts sold in the last 2 weeks, photo with a SOLD
     watermark across it. Works as proof that stock moves, and every card can start a request for
     "one like this". No price and no location are shown on purpose.
     Expects $recentlySold (collection of parts_inventory rows). --}}
@if(!empty($recentlySold) && $recentlySold->count())
<style>
  .sold-mark { display:inline-block; transform:rotate(-18deg); border:4px solid rgba(200,30,30,.88); color:rgba(200,30,30,.9);
               background:rgba(255,255,255,.35); font-weight:800; font-size:30px; letter-spacing:.18em; padding:0 14px 0 18px; line-height:1.25; }
  .sold-card img { filter:saturate(.75) brightness(.95); }
</style>
<section class="mt-10" id="recently-sold">
  <h3 class="font-display font-700 text-navy text-lg tracking-wide uppercase">Recently Sold</h3>
  <p class="text-xs text-gray-500 font-body mb-4">Parts we sold in the last 2 weeks. Need one like it? Tap <strong>Request one like this</strong> and we'll find it for you.</p>

  <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($recentlySold as $sp)
    @php
        $spPhotos = json_decode($sp->photos ?? '[]', true) ?: [];
        $spThumb  = !empty($spPhotos[0]) ? asset(config('media.prefix') . '/' . $spPhotos[0]) : asset('images/parts-photo-coming-soon.jpg');
        $spYears  = $sp->year_from . ((!empty($sp->year_to) && $sp->year_to != $sp->year_from) ? '–' . $sp->year_to : '');
        $spVehicle = trim($spYears . ' ' . $sp->brand . ' ' . $sp->model);
    @endphp
    <div class="sold-card bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm flex flex-col">
      <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
        <img src="{{ $spThumb }}" alt="{{ $sp->part_name }} — sold" loading="lazy" class="w-full h-full object-cover">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none"><span class="sold-mark">SOLD</span></div>
      </div>
      <div class="p-3 flex flex-col flex-1">
        <div class="font-display font-700 text-navy text-sm leading-tight">{{ $sp->part_name }}</div>
        <div class="text-xs text-gray-500 font-body mt-0.5">{{ $spVehicle }}</div>
        <div class="text-[11px] text-gray-400 font-body mt-0.5">Sold {{ \Carbon\Carbon::parse($sp->updated_at)->diffForHumans() }}</div>
        <button type="button" class="request-like mt-auto pt-3 text-left text-xs font-display font-700 text-gold hover:text-yellow-600 underline"
                data-vehicle="{{ $spVehicle }}" data-part="{{ $sp->part_name }}" data-id="{{ $sp->id }}">
          Request one like this
        </button>
      </div>
    </div>
    @endforeach
  </div>
</section>

<script>
// "Request one like this" -> fills the request form below and scrolls to it.
document.querySelectorAll('.request-like').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var box  = document.getElementById('request-part');
        var form = box ? box.querySelector('form') : null;
        if (!form) { if (box) box.scrollIntoView({behavior: 'smooth'}); return; }

        form.querySelector('[name="vehicle_text"]').value = btn.dataset.vehicle || '';
        form.querySelector('[name="part_text"]').value    = btn.dataset.part || '';
        var hid = form.querySelector('[name="part_id"]');
        if (!hid) { hid = document.createElement('input'); hid.type = 'hidden'; hid.name = 'part_id'; form.appendChild(hid); }
        hid.value = btn.dataset.id || '';

        var details = box.querySelector('details');
        if (details) details.open = true;
        box.scrollIntoView({behavior: 'smooth', block: 'start'});
        var nameField = form.querySelector('[name="customer_name"]');
        if (nameField) setTimeout(function () { nameField.focus({preventScroll: true}); }, 500);
    });
});
</script>
@endif
