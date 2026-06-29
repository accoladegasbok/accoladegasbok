{{-- FILE: resources/views/checkout/confirmation.blade.php --}}
@extends('layouts.app')
@section('title', 'Order Confirmed — {{ $order->order_ref }}')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">

  {{-- Success header --}}
  <div class="text-center mb-8">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-green-200">
      <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
      </svg>
    </div>
    <h1 class="font-display font-700 text-navy text-3xl tracking-wide mb-1">ORDER PLACED!</h1>
    <p class="text-gray-500 font-body text-sm">Reference: <span class="font-display font-700 text-navy text-lg tracking-widest">{{ $order->order_ref }}</span></p>
    <p class="text-gray-400 font-body text-xs mt-1">Save this reference — you will need it for payment and collection.</p>
  </div>

  {{-- Parts reserved notice --}}
  <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-sm text-blue-800 font-body text-center">
    <strong>Your parts are reserved for 24 hours.</strong> Complete payment within this time to secure your order.
  </div>

  {{-- ═══════════════════════════════════════════════════════
       BANK TRANSFER INSTRUCTIONS
  ═══════════════════════════════════════════════════════ --}}
  @if($order->payment_method === 'bank_transfer')
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase flex items-center gap-2">
      <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4z"/></svg>
      Transfer Payment Instructions
    </h2>

    {{-- Account details --}}
    <div class="bg-navy rounded-2xl p-5 mb-4">
      <div class="text-xs text-gray-400 uppercase tracking-widest mb-4 font-body">Send payment to</div>
      <div class="space-y-3">
        <div class="flex justify-between items-center">
          <span class="text-gray-400 text-sm font-body">Bank</span>
          <span class="text-white font-body font-500">{{ $bankName }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-400 text-sm font-body">Account name</span>
          <span class="text-white font-body font-500">{{ $accountName }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-400 text-sm font-body">Account number</span>
          <div class="flex items-center gap-3">
            <span class="font-display font-700 text-gold text-2xl tracking-widest">{{ $accountNo }}</span>
            <button onclick="copyText('{{ $accountNo }}', this)" class="text-xs text-gray-400 hover:text-white border border-gray-600 rounded-lg px-2.5 py-1 transition-colors font-body">Copy</button>
          </div>
        </div>
        <div class="border-t border-gray-700 pt-3 flex justify-between items-center">
          <span class="text-gray-400 text-sm font-body">Amount to transfer</span>
          <span class="font-display font-700 text-gold text-xl">{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-400 text-sm font-body">Narration / Reference</span>
          <div class="flex items-center gap-2">
            <span class="font-display font-700 text-white tracking-wider">{{ $order->order_ref }}</span>
            <button onclick="copyText('{{ $order->order_ref }}', this)" class="text-xs text-gray-400 hover:text-white border border-gray-600 rounded-lg px-2.5 py-1 transition-colors font-body">Copy</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Steps --}}
    <div class="space-y-3 mb-5">
      <div class="text-xs font-body font-500 text-gray-500 uppercase tracking-wider">After transferring:</div>
      @php
        $stepAmountFmt = $currencySymbol . ($currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2));
      @endphp
      @foreach([
        ['number' => '1', 'text' => 'Transfer exactly '.$stepAmountFmt.' to the Moniepoint account above.'],
        ['number' => '2', 'text' => 'Use '.$order->order_ref.' as the narration so we can match your payment.'],
        ['number' => '3', 'text' => 'Enter your bank reference below and WhatsApp us — we confirm within 1–2 hours.'],
      ] as $step)
      <div class="flex gap-3 items-start text-sm font-body text-gray-600">
        <span class="w-6 h-6 rounded-full bg-navy text-gold font-display font-700 text-xs flex items-center justify-center flex-shrink-0 mt-0.5">{{ $step['number'] }}</span>
        {{ $step['text'] }}
      </div>
      @endforeach
    </div>

    {{-- Reference submission form --}}
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4" id="referenceForm">
      <div class="text-sm font-body font-500 text-navy mb-3">Enter your bank transfer reference</div>
      <div class="flex gap-2">
        <input type="text" id="transferRef" placeholder="e.g. FBN/2024/123456789"
          class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-body focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold">
        <button onclick="submitRef()" id="submitRefBtn"
          class="bg-navy text-white font-body font-500 text-sm px-4 py-2.5 rounded-xl hover:bg-navy-dark transition-colors whitespace-nowrap">
          Send Reference
        </button>
      </div>
      <div id="refSuccess" class="hidden mt-2 text-xs text-green-600 font-body font-500"></div>
    </div>
  </div>

  {{-- ═══════════════════════════════════════════════════════
       POS IN-STORE INSTRUCTIONS
  ═══════════════════════════════════════════════════════ --}}
  @else
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase flex items-center gap-2">
      <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      POS Payment Instructions
    </h2>

    @php
      $stepAmountFmt2 = $currencySymbol . ($currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2));
    @endphp
    <div class="space-y-3 mb-5">
      @foreach([
        ['number' => '1', 'text' => 'Visit any of our offices listed below during business hours (Mon–Sat, 8am–6pm).'],
        ['number' => '2', 'text' => 'Show your Order Reference '.$order->order_ref.' to our staff.'],
        ['number' => '3', 'text' => 'Pay '.$stepAmountFmt2.' by card on our POS terminal.'],
        ['number' => '4', 'text' => 'Collect your part(s) immediately or arrange delivery with our team.'],
      ] as $step)
      <div class="flex gap-3 items-start text-sm font-body text-gray-600">
        <span class="w-6 h-6 rounded-full bg-navy text-gold font-display font-700 text-xs flex items-center justify-center flex-shrink-0 mt-0.5">{{ $step['number'] }}</span>
        {{ $step['text'] }}
      </div>
      @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      @foreach($posLocations as $name => $address)
      <div class="bg-gray-50 border border-gray-100 rounded-xl p-3.5 text-xs font-body">
        <div class="font-500 text-navy text-sm mb-1">{{ $name }}</div>
        <div class="text-gray-500 leading-relaxed">{{ $address }}</div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Order summary card --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-5">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide mb-4 uppercase">Parts in this order</h2>
    <div class="space-y-3">
      @foreach($items as $item)
      <div class="flex justify-between items-start text-sm font-body gap-4">
        <div>
          <div class="font-500 text-navy">{{ $item->part_name }}</div>
          <div class="text-xs text-gray-400">{{ $item->brand }} {{ $item->model }} · {{ $item->year_from }}@if($item->year_to!=$item->year_from)–{{ $item->year_to }}@endif · Grade {{ $item->condition_grade }}</div>
          <div class="text-xs text-gray-400 mt-0.5">{{ $item->location }} · {{ $item->part_code }}</div>
        </div>
        <div class="text-right flex-shrink-0">
          <div class="font-display font-700 text-navy">{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($item->unit_price_local ?? $item->unit_price_ngn) : number_format($item->unit_price_local ?? $item->unit_price_usd, 2) }}</div>
        </div>
      </div>
      @endforeach
      <div class="border-t border-gray-100 pt-3 flex justify-between font-body font-500 text-navy">
        <span>Total</span>
        <span class="font-display font-700 text-xl">{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2) }}</span>
      </div>
    </div>
  </div>

  {{-- WhatsApp confirm button --}}
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-5 text-center">
    <p class="text-sm font-body text-gray-500 mb-3">Questions? Our team is ready on WhatsApp.</p>
    @php
      $waAmountFmt3 = $currencySymbol . ($currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2));
      $waMsg = urlencode("Hi! I just placed order {$order->order_ref} for {$waAmountFmt3}. My name is {$order->customer_name}. Can you confirm my order?");
    @endphp
    <a href="https://wa.me/{{ $businessWa }}?text={{ $waMsg }}" target="_blank"
       class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-body font-500 text-sm px-6 py-3 rounded-xl transition-colors">
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
      Chat with us on WhatsApp
    </a>
  </div>

  <div class="text-center">
    <a href="{{ route('parts.search') }}" class="text-sm font-body text-gray-400 hover:text-navy transition-colors underline">
      Continue browsing parts →
    </a>
  </div>

</div>
@endsection

@push('scripts')
<script>
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}

async function submitRef() {
  const ref = document.getElementById('transferRef').value.trim();
  if (!ref) { alert('Please enter your bank transfer reference.'); return; }

  const btn = document.getElementById('submitRefBtn');
  btn.disabled = true;
  btn.textContent = 'Sending...';

  const res = await fetch('/checkout/transfer-proof', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: JSON.stringify({ order_ref: '{{ $order->order_ref }}', transfer_reference: ref }),
  });
  const data = await res.json();

  const success = document.getElementById('refSuccess');
  success.textContent = data.message;
  success.classList.remove('hidden');
  btn.textContent = 'Sent';
}
</script>
@endpush
