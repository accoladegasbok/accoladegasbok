{{-- FILE: resources/views/partials/_warranty-band.blade.php
     The "What Our Warranty Covers" band on the home page. The home page itself is wrapped in
     @verbatim, so it includes this partial between two @endverbatim / @verbatim markers.
     Wording comes from App\Support\Warranty. --}}
@php($__badge = \App\Support\Warranty::badge())
@php($__paras = \App\Support\Warranty::bandParagraphs())
  <!-- ══════ WARRANTY ══════ -->
  <section class="warranty" id="warranty">
    <div class="wrap warranty-inner">
      <div class="warranty-badge"><span class="n">{{ $__badge[0] }}</span>{{ $__badge[1] }}</div>
      <div>
        <h3>What Our Warranty Covers</h3>
        <p>{!! $__paras[0] !!}</p>
        <p>{!! $__paras[1] !!} <a href="{{ \App\Support\Warranty::URL_PATH }}" style="color:#5d4037;font-weight:700;text-decoration:underline;">Read the full warranty terms</a>.</p>
      </div>
    </div>
  </section>
