{{-- FILE: resources/views/partials/_home-makes.blade.php
     "Shop by make" chips for the home page. Makes that have parts in stock come first. --}}
@php
    try {
        $__stocked = \Illuminate\Support\Facades\DB::table('parts_inventory')
            ->whereNull('deleted_at')->where('status', 'Available')->whereNotNull('brand')
            ->selectRaw('UPPER(brand) as b, COUNT(*) as n')->groupBy('b')->orderByDesc('n')->limit(14)->pluck('n', 'b')->all();
    } catch (\Throwable $e) { $__stocked = []; }
    $__fallback = ['TOYOTA','LEXUS','HONDA','NISSAN','HYUNDAI','KIA','FORD','CHEVROLET','MERCEDES-BENZ','BMW','VOLKSWAGEN','SCION'];
    $__list = $__stocked ? array_keys($__stocked) : $__fallback;
@endphp
<style>
  .mk-strip{ display:flex; flex-wrap:wrap; gap:10px; }
  .mk-chip{ background:#fff; border:1.5px solid var(--navy-deep); border-radius:999px; padding:9px 18px; font-family:'Big Shoulders Display',sans-serif; text-transform:uppercase; font-weight:700; font-size:16px; letter-spacing:.03em; color:var(--navy-deep); box-shadow:3px 4px 0 rgba(10,31,92,.07); transition:transform .15s ease, background .15s ease, color .15s ease; }
  .mk-chip:hover{ background:var(--navy-deep); color:#fff; transform:translateY(-2px); }
  .mk-chip small{ font-family:'IBM Plex Mono',monospace; font-size:10px; opacity:.6; margin-left:6px; letter-spacing:.04em; }
</style>
<div class="mk-strip">
  @foreach($__list as $__mk)
    <a class="mk-chip" href="{{ route('parts.search', ['make' => $__mk]) }}">{{ $__mk }}@if(isset($__stocked[$__mk]))<small>{{ $__stocked[$__mk] }} in stock</small>@endif</a>
  @endforeach
</div>
