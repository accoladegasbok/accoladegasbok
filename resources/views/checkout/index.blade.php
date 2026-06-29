{{-- FILE: resources/views/checkout/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Checkout — Auto Zenith Parts')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

  <div class="flex items-center gap-3 mb-6">
    <a href="{{ route('cart.index') }}" class="text-gray-400 hover:text-navy transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="font-display font-700 text-navy text-2xl tracking-wide">CHECKOUT</h1>
  </div>

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 text-sm text-red-700 font-body">
      @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('checkout.store') }}" id="checkoutForm">
    @csrf
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      {{-- LEFT: Customer + Payment ─────────────────────────────────── --}}
      <div class="lg:col-span-2 space-y-5">

        {{-- Customer Details --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Your Details</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">Full Name *</label>
              <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">Phone *</label>
              <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold"
                placeholder="+234 or +1...">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">WhatsApp</label>
              <input type="tel" name="customer_whatsapp" value="{{ old('customer_whatsapp') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">Email</label>
              <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">City</label>
              <input type="text" name="customer_city" value="{{ old('customer_city') }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">Country *</label>
              <select name="customer_country" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body bg-white focus:outline-none focus:border-gold">
                <option value="Nigeria" @selected(!$isNigeria ? false : true)>Nigeria</option>
                <option value="Ghana">Ghana</option>
                <option value="USA" @selected(!$isNigeria)>USA</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
        </div>

        {{-- Fulfillment --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Collection or Delivery?</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
            <label class="flex items-start gap-3 border-2 border-navy rounded-2xl p-4 cursor-pointer">
              <input type="radio" name="fulfillment_type" value="collection" checked class="mt-0.5 accent-navy" onchange="toggleDelivery(this.value)">
              <div>
                <div class="font-body font-500 text-navy text-sm">Collect from office</div>
                <div class="text-xs text-gray-500 font-body mt-0.5">Pick up at any location — free.</div>
              </div>
            </label>
            <label class="flex items-start gap-3 border-2 border-gray-200 rounded-2xl p-4 cursor-pointer">
              <input type="radio" name="fulfillment_type" value="delivery" class="mt-0.5 accent-navy" onchange="toggleDelivery(this.value)">
              <div>
                <div class="font-body font-500 text-navy text-sm">Delivery to address</div>
                <div class="text-xs text-gray-500 font-body mt-0.5">Delivery cost quoted at confirmation.</div>
              </div>
            </label>
          </div>
          <div id="deliveryAddressField" class="hidden">
            <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1">Delivery Address *</label>
            <textarea name="delivery_address" rows="2" placeholder="Full delivery address"
              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold resize-none">{{ old('delivery_address') }}</textarea>
          </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             PAYMENT METHOD — switches between Nigeria and USA. Every
             amount shown here is the order's ONE real, fixed total —
             no FX conversion, no equivalent figure shown anywhere.
        ═══════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <h2 class="font-display font-700 text-navy text-base tracking-wide mb-1 uppercase">Payment Method</h2>
          <p class="text-xs text-gray-500 font-body mb-4">
            Total: <strong>{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2) }}</strong>
          </p>

          @if($isNigeria)
          {{-- ══ NIGERIA PAYMENT OPTIONS ══ --}}

          {{-- 1. Bank Transfer --}}
          <label class="flex items-start gap-4 border-2 border-gold rounded-2xl p-5 cursor-pointer mb-3">
            <input type="radio" name="payment_method" value="bank_transfer" checked class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-display font-700 text-navy text-sm tracking-wide">🏦 BANK TRANSFER</span>
                <span class="text-xs bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded-full font-500">No fees</span>
              </div>
              <div class="bg-navy rounded-xl p-4 text-white">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-3 font-body">Transfer to:</div>
                <div class="space-y-2 text-sm font-body">
                  <div class="flex justify-between"><span class="text-gray-400 text-xs">Bank</span><span class="font-500">{{ $ngBankName }}</span></div>
                  <div class="flex justify-between"><span class="text-gray-400 text-xs">Account Name</span><span class="font-500 text-xs">{{ $ngAccountName }}</span></div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-400 text-xs">Account No.</span>
                    <div class="flex items-center gap-2">
                      <span class="font-display font-700 text-gold text-lg tracking-widest">{{ $ngAccountNo }}</span>
                      <button type="button" onclick="copyText('{{ $ngAccountNo }}',this)" class="text-xs border border-gray-600 text-gray-400 hover:text-white rounded px-2 py-0.5">Copy</button>
                    </div>
                  </div>
                  <div class="flex justify-between pt-2 border-t border-gray-700">
                    <span class="text-gray-400 text-xs">Amount</span>
                    <span class="font-display font-700 text-gold text-lg">₦{{ number_format($totalLocal) }}</span>
                  </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-700 text-xs text-gray-400 font-body leading-relaxed">
                  Use your <span class="text-white font-500">Order Reference</span> as narration so we can match your payment quickly.
                </div>
              </div>
            </div>
          </label>

          {{-- 2. POS at Office --}}
          <label class="flex items-start gap-4 border-2 border-gray-200 rounded-2xl p-5 cursor-pointer mb-3 payment-option">
            <input type="radio" name="payment_method" value="pos_instore" class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-display font-700 text-navy text-sm tracking-wide">💳 POS AT OFFICE</span>
                <span class="text-xs bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full font-500">In-store</span>
              </div>
              <p class="text-xs text-gray-500 font-body mb-3">Visit any Nigerian office with your Order Reference to pay by card.</p>
              <div class="space-y-2">
                @foreach(['Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos'] as $loc)
                  @if(isset($posLocations[$loc]))
                  <div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-xs font-body">
                    <div class="font-500 text-navy mb-0.5">{{ $loc }}</div>
                    <div class="text-gray-500">{{ $posLocations[$loc] }}</div>
                  </div>
                  @endif
                @endforeach
              </div>
            </div>
          </label>

          {{-- 3. Paystack --}}
          <label class="flex items-start gap-4 border-2 border-gray-200 rounded-2xl p-5 cursor-pointer payment-option">
            <input type="radio" name="payment_method" value="paystack" class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-display font-700 text-navy text-sm tracking-wide">🔒 PAYSTACK</span>
                <span class="text-xs bg-purple-100 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-full font-500">Card / Bank / USSD</span>
              </div>
              <p class="text-xs text-gray-500 font-body">Pay securely online with Paystack — card, bank transfer, or USSD. 1.5% fee applies.</p>
            </div>
          </label>

          @else
          {{-- ══ USA PAYMENT OPTIONS ══ --}}

          {{-- 1. Zelle --}}
          <label class="flex items-start gap-4 border-2 border-gold rounded-2xl p-5 cursor-pointer mb-3">
            <input type="radio" name="payment_method" value="zelle" checked class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="font-display font-700 text-navy text-sm tracking-wide mb-2">📱 ZELLE</div>
              <div class="bg-navy rounded-xl p-4 text-white text-sm font-body">
                <div class="flex justify-between mb-1"><span class="text-gray-400 text-xs">Send to number</span><span class="font-display font-700 text-gold tracking-widest">{{ $usZelleNumber }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400 text-xs">Recipient name</span><span class="font-500 text-xs">{{ $usZelleName }}</span></div>
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-700"><span class="text-gray-400 text-xs">Amount</span><span class="font-display font-700 text-gold">${{ number_format($totalLocal, 2) }}</span></div>
              </div>
            </div>
          </label>

          {{-- 2. CashApp --}}
          <label class="flex items-start gap-4 border-2 border-gray-200 rounded-2xl p-5 cursor-pointer mb-3 payment-option">
            <input type="radio" name="payment_method" value="cashapp" class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="font-display font-700 text-navy text-sm tracking-wide mb-2">💚 CASHAPP</div>
              <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-body">
                <div class="flex justify-between"><span class="text-gray-500 text-xs">CashApp tag</span><span class="font-display font-700 text-navy">{{ $usCashApp }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-gray-500 text-xs">Amount</span><span class="font-display font-700 text-navy">${{ number_format($totalLocal, 2) }}</span></div>
              </div>
            </div>
          </label>

          {{-- 3. Venmo --}}
          <label class="flex items-start gap-4 border-2 border-gray-200 rounded-2xl p-5 cursor-pointer mb-3 payment-option">
            <input type="radio" name="payment_method" value="venmo" class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="font-display font-700 text-navy text-sm tracking-wide mb-2">💙 VENMO</div>
              <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-body">
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Venmo number</span><span class="font-display font-700 text-navy">{{ $usVenmo }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-gray-500 text-xs">Amount</span><span class="font-display font-700 text-navy">${{ number_format($totalLocal, 2) }}</span></div>
              </div>
            </div>
          </label>

          {{-- 4. Cash --}}
          <label class="flex items-start gap-4 border-2 border-gray-200 rounded-2xl p-5 cursor-pointer mb-3 payment-option">
            <input type="radio" name="payment_method" value="cash" class="mt-1 accent-navy" onchange="switchPayment(this.value)">
            <div class="flex-1">
              <div class="font-display font-700 text-navy text-sm tracking-wide mb-2">💵 CASH (USD) AT OFFICE</div>
              <div class="space-y-2">
                @foreach(['Waxahachie TX','Elkhorn WI'] as $loc)
                  @if(isset($posLocations[$loc]))
                  <div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-xs font-body">
                    <div class="font-500 text-navy mb-0.5">{{ $loc }}</div>
                    <div class="text-gray-500">{{ $posLocations[$loc] }}</div>
                  </div>
                  @endif
                @endforeach
              </div>
            </div>
          </label>

          @endif

        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-2">Notes (optional)</label>
          <textarea name="notes" rows="2" placeholder="Any special requests, preferred collection time..."
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body focus:outline-none focus:border-gold resize-none">{{ old('notes') }}</textarea>
        </div>

      </div>

      {{-- RIGHT: Order summary ─────────────────────────────────────── --}}
      <div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sticky top-20">
          <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Order Summary</h2>
          <div class="space-y-3 mb-5">
            @foreach($items as $item)
            <div class="flex gap-3 items-start">
              <div class="flex-1 min-w-0">
                <div class="text-xs text-navy font-body font-500 leading-tight truncate">{{ $item['part_name'] }}</div>
                <div class="text-xs text-gray-400 font-body">{{ $item['brand'] }} {{ $item['model'] }}</div>
              </div>
              <div class="text-right flex-shrink-0">
                <div class="text-xs font-body font-500 text-navy">{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($item['unit_price_local']) : number_format($item['unit_price_local'], 2) }}</div>
              </div>
            </div>
            @endforeach
          </div>

          <div class="border-t border-gray-100 pt-4 space-y-2 text-sm font-body">
            <div class="flex justify-between font-500 text-navy text-base pt-1">
              <span>Total</span>
              <span class="font-display font-700 text-xl">{{ $currencySymbol }}{{ $currencyCode === 'NGN' ? number_format($totalLocal) : number_format($totalLocal, 2) }}</span>
            </div>
          </div>

          <button type="submit" id="placeOrderBtn"
            class="mt-5 w-full bg-navy hover:bg-navy-dark text-white font-display font-700 text-sm py-3.5 rounded-xl tracking-wide transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            PLACE ORDER
          </button>
          <p class="text-xs text-center text-gray-400 font-body mt-3 leading-relaxed">
            Parts are reserved for 24 hours on order placement.
          </p>
        </div>
      </div>

    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
function toggleDelivery(val) {
  document.getElementById('deliveryAddressField').classList.toggle('hidden', val !== 'delivery');
}
function switchPayment(method) {
  document.querySelectorAll('label.payment-option').forEach(l => {
    l.classList.remove('border-gold');
    l.classList.add('border-gray-200');
  });
}
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = orig, 2000);
  });
}
document.getElementById('checkoutForm').addEventListener('submit', function() {
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Placing order...';
});
</script>
@endpush
