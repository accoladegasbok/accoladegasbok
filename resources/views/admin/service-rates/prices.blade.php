{{-- FILE: resources/views/admin/service-rates/prices.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit Prices — ' . $service->name)
@section('page-title', $service->name)
@section('page-sub','Set this service\'s real price for each location — no FX conversion, each number is fixed and final')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm font-body mb-5 max-w-2xl">
  Set the real Naira/Cedi/Dollar price for each location individually — don't just copy the same number across currencies. A labor charge that's $50 in Texas is NOT ₦50 in Lagos; it should reflect what you'd actually charge there.
</div>

<form method="POST" action="{{ route('admin.service-rates.prices.update', $service->id) }}" class="max-w-2xl">
@csrf
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Currency</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Price</th>
      </tr>
    </thead>
    <tbody>
      @foreach($locations as $loc)
      @php
        $currencyCode = str_contains($loc, 'Nigeria') ? 'NGN' : (str_contains($loc, 'Ghana') ? 'GHS' : 'USD');
        $symbol = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$currencyCode];
        $existing = $prices[$loc] ?? null;
      @endphp
      <tr class="border-b border-gray-50 last:border-0">
        <td class="px-4 py-3 font-500 text-navy">{{ $loc }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $currencyCode }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <span class="text-gray-400">{{ $symbol }}</span>
            <input type="number" name="prices[{{ $loc }}]" step="0.01" min="0"
              value="{{ old('prices.'.$loc, $existing->price_local ?? '') }}"
              placeholder="0.00"
              class="w-32 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-gold">
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="flex gap-3 justify-end mt-5 pb-8">
  <a href="{{ route('admin.service-rates.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Save All Prices</button>
</div>
</form>
@endsection
