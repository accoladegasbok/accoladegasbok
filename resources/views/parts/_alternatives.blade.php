{{-- FILE: resources/views/parts/_alternatives.blade.php
     "Also fits your vehicle": verified alternatives for customers, plus (staff only) broader unverified leads.
     Expects: $alternatives, $staffLeads, $altLabel (from PartsSearchController@index). --}}
@php
    $__alts  = $alternatives ?? collect();
    $__leads = $staffLeads ?? collect();
    $__card = function ($part) {
        $photos = json_decode($part->photos ?? '[]', true) ?: [];
        $thumb  = !empty($photos[0]) ? asset(config('media.prefix') . '/' . $photos[0]) : asset('images/parts-photo-coming-soon.jpg');
        $cc  = $part->currency_code ?? 'USD';
        $sym = match ($cc) { 'NGN' => '₦', 'GHS' => 'GH₵', 'GBP' => '£', default => '$' };
        $price = $sym . number_format($part->price_local ?? $part->price_usd ?? 0, $cc === 'NGN' ? 0 : 2);
        return compact('part', 'thumb', 'price');
    };
@endphp

@if($__alts->count())
<section class="mt-10" id="verified-alternatives">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-1">
        <h2 class="font-display font-700 text-navy text-lg">Also fits your {{ $altLabel ?: 'vehicle' }}</h2>
        <span class="text-xs font-body font-500 px-2.5 py-1 rounded-full bg-green-100 text-green-800">✓ Verified alternatives</span>
    </div>
    <p class="text-sm font-body text-gray-500 mb-4">These are from a different model or year, but we have checked that they fit. Same warranty, same price terms.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($__alts as $part)
            @php $c = $__card($part); @endphp
            <div class="bg-white rounded-2xl border border-green-200 shadow-sm overflow-hidden flex flex-col">
                <a href="{{ route('parts.show', $part->id) }}" class="block bg-gray-100 aspect-[4/3] overflow-hidden">
                    <img src="{{ $c['thumb'] }}" alt="{{ $part->part_name }}" class="w-full h-full object-cover" loading="lazy">
                </a>
                <div class="p-4 flex flex-col flex-1">
                    <div class="font-display font-700 text-navy text-sm tracking-wide">
                        {{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif {{ $part->brand }} {{ $part->model }}
                    </div>
                    <a href="{{ route('parts.show', $part->id) }}" class="font-body font-500 text-gray-600 text-sm leading-tight mb-1 hover:text-az-blue">{{ $part->part_name }}</a>
                    @include('parts._specs', ['part' => $part, 'style' => 'chips'])
                    <div class="mt-auto pt-2 flex items-center justify-between">
                        <span class="font-display font-700 text-navy">{{ $c['price'] }}</span>
                        <span class="text-xs font-body text-gray-500">{{ \App\Support\HubLocations::publicLabel($part->location) }}</span>
                    </div>
                    <span class="mt-2 inline-block self-start text-xs font-body font-500 px-2 py-0.5 rounded-full bg-green-100 text-green-800">{{ $part->fit_label }}</span>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($__leads->count())
<section class="mt-8 bg-amber-50 border border-amber-200 rounded-2xl p-4" id="staff-alternatives">
    <div class="text-xs font-display font-700 text-amber-800 uppercase tracking-wide mb-1">Staff only: other possible fits (not verified)</div>
    <p class="text-xs font-body text-amber-700 mb-3">Customers do not see these. Confirm on the unit or in the Compatibility Checker before quoting.
        <a href="{{ route('admin.compatibility.index') }}" class="underline">Open the checker</a></p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($__leads as $part)
            @php $c = $__card($part); @endphp
            <a href="{{ route('parts.show', $part->id) }}" class="block bg-white rounded-xl border border-amber-200 p-3 hover:border-amber-400">
                <div class="text-sm font-body font-500 text-navy">{{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif {{ $part->brand }} {{ $part->model }}</div>
                <div class="text-xs text-gray-600">{{ $part->part_name }} · {{ $c['price'] }} · {{ \App\Support\HubLocations::publicLabel($part->location) }}</div>
                <div class="text-xs text-amber-700 mt-1">{{ $part->fit_label }}@if($part->group_code) · IC# {{ $part->group_code }}@endif</div>
            </a>
        @endforeach
    </div>
</section>
@endif
