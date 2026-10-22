{{-- FILE: resources/views/partials/_cat-icon.blade.php
     Line icon for a part group. Usage: @include('partials._cat-icon', ['slug' => $tile['slug']]) --}}
@php $__s = $slug ?? ''; @endphp
<svg class="cat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($__s)
    @case('engines')
        <path d="M3 11h2V8h4V6h5v2h3v3h2v5h-2v2H7v-2H5v-3H3z"/><path d="M9 12h6"/><path d="M9 15h6"/>
        @break
    @case('transmissions')
        <circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="7"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>
        @break
    @case('suspension')
        <path d="M8 3h8M12 3v3"/><path d="M8 6h8"/><path d="M9 8l6 2-6 2 6 2-6 2"/><path d="M8 20h8M12 18v2"/>
        @break
    @case('brakes')
        <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><circle cx="12" cy="6.5" r=".7"/><circle cx="12" cy="17.5" r=".7"/><circle cx="6.5" cy="12" r=".7"/><circle cx="17.5" cy="12" r=".7"/>
        @break
    @case('wheels')
        <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2"/><path d="M12 10V3M13.9 11.3l6.6-2.1M13.2 13.6l4 5.6M10.8 13.6l-4 5.6M10.1 11.3L3.5 9.2"/>
        @break
    @case('body')
        <path d="M3 15l1.6-5A2 2 0 016.5 8.5h11a2 2 0 011.9 1.5L21 15v3h-2v-1.5H5V18H3z"/><path d="M5 12h14"/><circle cx="7.5" cy="14.2" r=".8"/><circle cx="16.5" cy="14.2" r=".8"/>
        @break
    @case('interior')
        <path d="M8 3h4a3 3 0 013 3v6h3a2 2 0 012 2v3H8a3 3 0 01-3-3V6a3 3 0 013-3z"/><path d="M8 21h12"/>
        @break
    @case('electrical')
        <path d="M13 2L5 13h6l-1 9 8-11h-6z"/>
        @break
    @case('cooling')
        <rect x="4" y="6" width="16" height="12" rx="2"/><path d="M8 6v12M12 6v12M16 6v12M2 10h2M2 14h2M20 10h2M20 14h2"/>
        @break
    @case('fuel-exhaust')
        <path d="M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z"/><path d="M9.5 15a2.5 2.5 0 002.5 2.5"/>
        @break
    @case('airbags')
        <circle cx="12" cy="9" r="6"/><path d="M6 21c1-3.5 3-5.5 6-5.5s5 2 6 5.5"/>
        @break
    @default
        <path d="M4 8l8-4 8 4v8l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v8"/>
@endswitch
</svg>
