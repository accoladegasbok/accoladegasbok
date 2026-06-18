{{-- FILE: resources/views/checkout/cart.blade.php --}}
@extends('layouts.app')
@section('title', 'Your Cart — Auto Zenith Parts')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

  {{-- Header --}}
  <div class="flex items-center gap-3 mb-6">
    <a href="{{ route('parts.search') }}" class="text-gray-400 hover:text-navy transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="font-display font-700 text-navy text-2xl tracking-wide">YOUR CART</h1>
    <span class="bg-navy text-gold text-xs font-display font-700 px-2.5 py-1 rounded-full">{{ count($items) }}</span>
  </div>

  @if(empty($items))
    <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center shadow-sm">
      <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <h3 class="font-display font-700 text-navy text-xl mb-2">Cart is empty</h3>
      <p class="text-gray-500 text-sm mb-5">Browse our inventory to find the part you need.</p>
      <a href="{{ route('parts.search') }}" class="inline-block bg-navy text-white font-display font-700 text-sm px-6 py-3 rounded-xl">Search Parts</a>
    </div>

  @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      {{-- Cart Items --}}
      <div class="lg:col-span-2 space-y-3" id="cartItemsList">
        @foreach($items as $item)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm flex gap-4 p-4 items-start cart-item" data-part-id="{{ $item['part_id'] }}">
          {{-- Thumb --}}
          <div class="w-20 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
            @if($item['thumb'])
              <img src="{{ $item['thumb'] }}" class="w-full h-full object-cover" loading="lazy">
            @else
              <div class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
            @endif
          </div>

          {{-- Details --}}
          <div class="flex-1 min-w-0">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-0.5 font-body">
              {{ $item['brand'] }} {{ $item['model'] }} · {{ $item['year_from'] }}@if($item['year_to']!=$item['year_from'])–{{ $item['year_to'] }}@endif
            </div>
            <div class="font-display font-700 text-navy text-base leading-tight">{{ $item['part_name'] }}</div>
            <div class="flex gap-3 mt-1 text-xs text-gray-400 font-body">
              <span class="px-2 py-0.5 rounded-full text-xs font-500
                @if($item['condition_grade']==='A') bg-green-50 text-green-700 border border-green-200
                @elseif($item['condition_grade']==='B') bg-blue-50 text-blue-700 border border-blue-200
                @else bg-amber-50 text-amber-700 border border-amber-200 @endif">
                Grade {{ $item['condition_grade'] }}
              </span>
              <span>{{ $item['location'] }}</span>
            </div>
          </div>

          {{-- Price + Remove --}}
          <div class="text-right flex-shrink-0">
            <div class="font-display font-800 text-navy text-lg">₦{{ number_format($item['unit_price_ngn']) }}</div>
            <div class="text-xs text-gray-400 font-body">${{ number_format($item['unit_price_usd'], 2) }}</div>
            <button class="remove-item mt-2 text-xs text-red-400 hover:text-red-600 font-body underline" data-part-id="{{ $item['part_id'] }}">Remove</button>
          </div>
        </div>
        @endforeach
      </div>

      {{-- Order Summary --}}
      <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sticky top-20">
          <h2 class="font-display font-700 text-navy text-lg mb-4 tracking-wide">ORDER SUMMARY</h2>

          <div class="space-y-2 mb-4 text-sm font-body">
            <div class="flex justify-between text-gray-500">
              <span>{{ count($items) }} part{{ count($items)>1?'s':'' }}</span>
              <span>${{ number_format($totalUsd, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
              <span>Rate (₦/$)</span>
              <span>{{ number_format($rate) }}</span>
            </div>
            <div class="border-t border-gray-100 pt-2 flex justify-between font-500 text-navy">
              <span>Total (Naira)</span>
              <span id="cartTotal" class="font-display font-700 text-xl">₦{{ number_format($totalNgn) }}</span>
            </div>
          </div>

          {{-- Payment note --}}
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 text-xs font-body text-amber-800 leading-relaxed">
            <div class="font-500 mb-1">Payment options:</div>
            <div>• Bank transfer to our <strong>Moniepoint</strong> account</div>
            <div>• POS payment at any of our offices</div>
          </div>

          <a href="{{ route('checkout.index') }}" class="block w-full bg-navy hover:bg-navy-dark text-white text-center font-display font-700 text-sm py-3.5 rounded-xl transition-colors tracking-wide">
            PROCEED TO CHECKOUT →
          </a>
          <a href="{{ route('parts.search') }}" class="block w-full text-center text-xs font-body text-gray-400 hover:text-navy mt-3 transition-colors">
            ← Continue shopping
          </a>
        </div>
      </div>

    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.remove-item').forEach(btn => {
  btn.addEventListener('click', async function() {
    const partId  = this.dataset.partId;
    const itemRow = document.querySelector(`.cart-item[data-part-id="${partId}"]`);

    btn.textContent = 'Removing...';
    btn.disabled = true;

    const res  = await fetch('/cart/remove', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({ part_id: partId }),
    });
    const data = await res.json();

    if (data.success) {
      itemRow?.remove();
      document.getElementById('cartTotal').textContent = '₦' + data.totalNgn;
      // Update nav badge
      document.querySelectorAll('.cart-badge').forEach(b => b.textContent = data.count);
      if (data.count === 0) location.reload();
    }
  });
});
</script>
@endpush
