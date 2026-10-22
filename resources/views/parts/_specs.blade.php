{{-- FILE: resources/views/parts/_specs.blade.php
     Compatibility specs for one part, chosen by part type (see App\Support\PartSpecs).
     Usage:  @include('parts._specs', ['part' => $part, 'style' => 'chips'])   search cards / list rows
             @include('parts._specs', ['part' => $part, 'style' => 'table'])   part page
     Pin count only ever appears for gearbox parts; drive type for suspension, brakes and gearboxes. --}}
@php
    $__style = $style ?? 'chips';
    $__toneClass = [
        'code'  => 'bg-navy text-gold font-mono',
        'dark'  => 'bg-gray-700 text-white font-mono',
        'pin'   => 'bg-navy text-gold font-mono',
        'drive' => 'bg-blue-600 text-white font-body',
        'plain' => 'bg-gray-100 text-gray-600 font-body',
    ];
@endphp

@if($__style === 'table')
    @php $__rows = \App\Support\PartSpecs::rows($part); @endphp
    @if(count($__rows))
    <div class="border border-gray-200 rounded-2xl overflow-hidden text-sm font-body mb-4">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider font-500">Fitment details &mdash; check these against your vehicle</div>
        @foreach($__rows as $__r)
            <div class="flex gap-4 px-4 py-2.5 border-b border-gray-100 last:border-0">
                <span class="text-gray-400 font-500 w-36 flex-shrink-0">{{ $__r['label'] }}</span>
                @if($__r['missing'])
                    <span class="text-amber-700 text-xs">Not recorded &mdash; <a href="https://wa.me/{{ \App\Support\Contacts::waFor($part->location ?? null) }}" target="_blank" class="underline">ask us</a></span>
                @else
                    <span class="text-navy font-500 font-mono text-xs">{{ $__r['value'] }}</span>
                @endif
            </div>
        @endforeach
        @if(collect($__rows)->contains(fn($r) => $r['label'] === 'Pin count'))
            <div class="px-4 py-2 bg-gray-50 text-xs text-gray-500">Pin count is the number of terminals on the connector at the valve body, just above the selector switch.</div>
        @endif
    </div>
    @endif
@else
    @php $__chips = \App\Support\PartSpecs::chips($part); @endphp
    @if(count($__chips))
    <div class="flex flex-wrap gap-1 mb-1">
        @foreach($__chips as $__c)
            <span class="text-[10px] font-700 {{ $__toneClass[$__c['tone']] ?? $__toneClass['plain'] }} px-1.5 py-0.5 rounded" title="{{ $__c['label'] }}">{{ $__c['value'] }}</span>
        @endforeach
    </div>
    @endif
@endif
