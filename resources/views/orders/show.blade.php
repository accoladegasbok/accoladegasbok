{{-- FILE: resources/views/admin/orders/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Order ' . $order->order_ref)
@section('page-title', $order->order_ref)
@section('page-sub', 'Order detail · ' . $order->customer_name . ' · ' . \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i'))

@section('header-actions')
<a href="{{ route('admin.orders.index') }}" class="text-xs font-body text-gray-400 hover:text-navy flex items-center gap-1 transition-colors">
  ← All orders
</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- ════════════════════════════════════════════════════════════════
       LEFT (2 cols) — Items + actions
  ════════════════════════════════════════════════════════════════ --}}
  <div class="lg:col-span-2 space-y-5">

    {{-- Status bar --}}
    @php
      $steps  = ['awaiting_payment','confirmed','processing','ready_for_collection','completed'];
      $curIdx = array_search($order->order_status, $steps);
    @endphp
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase">Order Progress</h2>
        @if($order->order_status === 'cancelled')
          <span class="badge badge-red">Cancelled</span>
        @endif
      </div>
      <div class="flex items-center gap-0">
        @foreach($steps as $i => $step)
        <div class="flex-1 flex flex-col items-center gap-1.5 relative">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-display font-700 z-10
            {{ $i <= $curIdx && $order->order_status !== 'cancelled'
              ? 'bg-navy text-gold'
              : 'bg-gray-200 text-gray-400' }}">
            {{ $i < $curIdx ? '✓' : $i + 1 }}
          </div>
          <div class="text-center text-xs font-body text-gray-400 leading-tight px-1">
            {{ str_replace('_',' ',ucfirst($step)) }}
          </div>
          @if($i < count($steps)-1)
            <div class="absolute top-3.5 left-1/2 w-full h-0.5 {{ $i < $curIdx ? 'bg-navy' : 'bg-gray-200' }}"></div>
          @endif
        </div>
        @endforeach
      </div>
    </div>

    {{-- Order items --}}
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">
        Parts ({{ count($items) }})
      </h2>
      <div class="space-y-3">
        @foreach($items as $item)
        <div class="flex items-start gap-4 py-3 border-b border-gray-50 last:border-0">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-display font-700 text-navy text-sm">{{ $item->part_name }}</span>
              <span class="font-mono text-xs text-gray-400">{{ $item->part_code }}</span>
              <span class="badge {{ $item->condition_grade==='A'?'badge-green':($item->condition_grade==='B'?'badge-blue':'badge-amber') }}">
                Grade {{ $item->condition_grade }}
              </span>
            </div>
            <div class="text-xs text-gray-500 font-body mt-1">
              {{ $item->brand }} {{ $item->model }} ·
              {{ $item->year_from }}@if($item->year_to!=$item->year_from)–{{ $item->year_to }}@endif ·
              {{ $item->location }}
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <div class="font-display font-700 text-navy text-base">₦{{ number_format($item->unit_price_ngn) }}</div>
            <div class="text-xs text-gray-400">${{ number_format($item->unit_price_usd, 2) }}</div>
          </div>
        </div>
        @endforeach

        <div class="flex justify-between pt-2 font-display font-700 text-navy text-base">
          <span>Total</span>
          <span>₦{{ number_format($order->total_amount_ngn) }}</span>
        </div>
      </div>
    </div>

    {{-- Actions --}}
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Actions</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        {{-- Confirm payment --}}
        @if(in_array($order->payment_status, ['pending','transfer_sent','payment_pending_confirmation']))
        <div class="border border-green-200 bg-green-50 rounded-xl p-4">
          <div class="text-xs font-body font-500 text-green-700 mb-2">Confirm bank transfer received</div>
          <button onclick="confirmPayment({{ $order->id }})"
            class="w-full bg-green-500 hover:bg-green-600 text-white font-display font-700 text-xs py-2.5 rounded-xl tracking-wide transition-colors">
            ✓ Confirm Payment Received
          </button>
        </div>
        @endif

        {{-- Update status --}}
        <div class="border border-gray-200 rounded-xl p-4">
          <div class="text-xs font-body font-500 text-gray-600 mb-2">Update order status</div>
          <select id="statusSelect" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-body bg-white focus:outline-none mb-2">
            @foreach(['awaiting_payment','payment_pending_confirmation','confirmed','processing','ready_for_collection','shipped','completed','cancelled'] as $s)
              <option value="{{ $s }}" {{ $order->order_status===$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
            @endforeach
          </select>
          <textarea id="staffNotes" rows="1" placeholder="Staff note (optional)"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none resize-none mb-2">{{ $order->staff_notes }}</textarea>
          <button onclick="updateStatus({{ $order->id }})"
            class="w-full bg-navy text-white font-display font-700 text-xs py-2.5 rounded-xl tracking-wide hover:bg-navy-light transition-colors">
            Update Status
          </button>
        </div>

        {{-- WhatsApp customer --}}
        @php
          $waNum = ltrim($order->customer_whatsapp ?? $order->customer_phone, '+');
          $waMsg = urlencode("Hi {$order->customer_name}, regarding your Auto Zenith Parts order {$order->order_ref}.");
          $receiptUrl = route('orders.receipt.public', $order->order_ref);
          $waReceiptMsg = urlencode("Hi {$order->customer_name}, here's your receipt for order {$order->order_ref}: {$receiptUrl}");
        @endphp
        <a href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}" target="_blank"
          class="border border-green-200 bg-green-50 rounded-xl p-4 flex items-center gap-3 hover:bg-green-100 transition-colors">
          <svg class="w-8 h-8 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
          <div>
            <div class="text-xs font-body font-500 text-green-700">Message customer</div>
            <div class="text-xs text-green-600">{{ $order->customer_phone }}</div>
          </div>
        </a>

        {{-- Receipt actions --}}
        <div class="border border-gray-200 rounded-xl p-4 sm:col-span-2">
          <div class="text-xs font-body font-500 text-gray-600 mb-1">Receipt</div>
          <p class="text-xs text-gray-400 font-body mb-3">Print produces all 4 internal copies. Email/WhatsApp send only the single customer-facing copy.</p>
          <div class="grid grid-cols-3 gap-2">
            <a href="{{ route('admin.orders.print', $order->id) }}" target="_blank"
              class="flex flex-col items-center gap-1.5 border border-gray-200 rounded-xl py-3 hover:border-navy hover:bg-gray-50 transition-colors">
              <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
              <span class="text-xs font-body text-gray-600">Print (×4)</span>
            </a>
            <button onclick="emailReceipt({{ $order->id }})" id="emailReceiptBtn"
              class="flex flex-col items-center gap-1.5 border border-gray-200 rounded-xl py-3 hover:border-navy hover:bg-gray-50 transition-colors">
              <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
              <span class="text-xs font-body text-gray-600">Email</span>
            </button>
            <a href="https://wa.me/{{ $waNum }}?text={{ $waReceiptMsg }}" target="_blank"
              class="flex flex-col items-center gap-1.5 border border-gray-200 rounded-xl py-3 hover:border-navy hover:bg-gray-50 transition-colors">
              <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
              <span class="text-xs font-body text-gray-600">WhatsApp</span>
            </a>
          </div>
          @if(!$order->customer_email)
            <p class="text-xs text-amber-600 font-body mt-2">No email on file — email option will fail until one is added.</p>
          @endif
          <div id="emailReceiptFeedback" class="text-xs font-body mt-2"></div>
        </div>

      </div>
    </div>

  </div>

  {{-- ════════════════════════════════════════════════════════════════
       RIGHT (1 col) — Customer info + payment details
  ════════════════════════════════════════════════════════════════ --}}
  <div class="space-y-5">

    {{-- Customer --}}
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">Customer</h2>
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center flex-shrink-0">
          <span class="font-display font-700 text-gold text-sm">{{ substr($order->customer_name,0,1) }}</span>
        </div>
        <div>
          <div class="font-500 text-navy text-sm">{{ $order->customer_name }}</div>
          <div class="text-xs text-gray-400">{{ $order->customer_country }}</div>
        </div>
      </div>
      <div class="space-y-1.5 text-xs font-body">
        <div class="flex justify-between"><span class="text-gray-400">Phone</span><span class="font-500">{{ $order->customer_phone }}</span></div>
        @if($order->customer_whatsapp)<div class="flex justify-between"><span class="text-gray-400">WhatsApp</span><span class="font-500 text-green-600">{{ $order->customer_whatsapp }}</span></div>@endif
        @if($order->customer_email)<div class="flex justify-between"><span class="text-gray-400">Email</span><span class="font-500">{{ $order->customer_email }}</span></div>@endif
        @if($order->customer_city)<div class="flex justify-between"><span class="text-gray-400">City</span><span class="font-500">{{ $order->customer_city }}</span></div>@endif
        <div class="flex justify-between"><span class="text-gray-400">Fulfillment</span><span class="font-500">{{ ucfirst($order->fulfillment_type) }}</span></div>
        @if($order->delivery_address)
          <div class="mt-2 bg-gray-50 rounded-lg p-2.5 text-gray-600 leading-relaxed">{{ $order->delivery_address }}</div>
        @endif
      </div>
    </div>

    {{-- Payment details --}}
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">Payment Details</h2>
      <div class="space-y-2 text-xs font-body">
        <div class="flex justify-between">
          <span class="text-gray-400">Method</span>
          <span class="font-500">{{ $order->payment_method === 'bank_transfer' ? '🏦 Bank Transfer' : '💳 POS In-Store' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Status</span>
          <span class="badge
            @if($order->payment_status==='confirmed') badge-green
            @elseif($order->payment_status==='transfer_sent') badge-blue
            @elseif($order->payment_status==='pending') badge-amber
            @else badge-gray @endif">
            {{ str_replace('_',' ',$order->payment_status) }}
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Amount (₦)</span>
          <span class="font-display font-700 text-navy text-sm">₦{{ number_format($order->total_amount_ngn) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Amount (USD)</span>
          <span class="font-500">${{ number_format($order->total_amount_usd, 2) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Rate used</span>
          <span class="font-500 font-mono">{{ number_format($order->exchange_rate) }}</span>
        </div>
        @if($order->transfer_reference)
        <div class="mt-2 bg-blue-50 border border-blue-100 rounded-lg p-2.5">
          <div class="text-blue-600 font-500 mb-0.5">Transfer reference from customer</div>
          <div class="font-mono text-navy">{{ $order->transfer_reference }}</div>
          @if($order->transfer_claimed_at)
            <div class="text-gray-400 mt-1">Submitted: {{ \Carbon\Carbon::parse($order->transfer_claimed_at)->format('d M Y H:i') }}</div>
          @endif
        </div>
        @endif
        @if($order->payment_confirmed_at)
        <div class="bg-green-50 border border-green-100 rounded-lg p-2.5">
          <div class="text-green-600 font-500 text-xs">✓ Confirmed by {{ $order->confirmed_by }}</div>
          <div class="text-gray-400 text-xs mt-0.5">{{ \Carbon\Carbon::parse($order->payment_confirmed_at)->format('d M Y H:i') }}</div>
        </div>
        @endif
      </div>
    </div>

    {{-- Notes --}}
    @if($order->notes || $order->staff_notes)
    <div class="stat-card">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">Notes</h2>
      @if($order->notes)
        <div class="text-xs font-body text-gray-500 mb-1 uppercase tracking-wider">Customer note</div>
        <div class="bg-gray-50 rounded-xl p-3 text-sm font-body text-gray-600 mb-3 leading-relaxed">{{ $order->notes }}</div>
      @endif
      @if($order->staff_notes)
        <div class="text-xs font-body text-gray-500 mb-1 uppercase tracking-wider">Staff note</div>
        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 text-sm font-body text-amber-800 leading-relaxed">{{ $order->staff_notes }}</div>
      @endif
    </div>
    @endif

    {{-- Timestamps --}}
    <div class="stat-card text-xs font-body text-gray-400 space-y-1">
      <div class="flex justify-between"><span>Order placed</span><span>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') }}</span></div>
      <div class="flex justify-between"><span>Last updated</span><span>{{ \Carbon\Carbon::parse($order->updated_at)->format('d M Y H:i') }}</span></div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function confirmPayment(id) {
  if (!confirm('Confirm payment received for this order?')) return;
  const res  = await fetch(`/admin/orders/${id}/confirm-payment`, {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
    body: JSON.stringify({ confirmed_by: '{{ session("staff_name") }}' }),
  });
  const data = await res.json();
  if (data.success) { location.reload(); }
  else { alert('Error. Try again.'); }
}

async function updateStatus(id) {
  const status = document.getElementById('statusSelect').value;
  const notes  = document.getElementById('staffNotes').value;
  const res  = await fetch(`/admin/orders/${id}/status`, {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
    body: JSON.stringify({ order_status: status, staff_notes: notes }),
  });
  const data = await res.json();
  if (data.success) { location.reload(); }
  else { alert('Could not update status. Please try again.'); }
}

async function emailReceipt(id) {
  const btn = document.getElementById('emailReceiptBtn');
  const feedback = document.getElementById('emailReceiptFeedback');
  btn.disabled = true;
  feedback.textContent = 'Sending...';
  feedback.className = 'text-xs font-body mt-2 text-gray-400';

  try {
    const res = await fetch(`/admin/orders/${id}/email-receipt`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    const data = await res.json();
    if (data.success) {
      feedback.textContent = '✓ ' + data.message;
      feedback.className = 'text-xs font-body mt-2 text-green-600';
    } else {
      feedback.textContent = data.error || 'Could not send email.';
      feedback.className = 'text-xs font-body mt-2 text-red-600';
    }
  } catch (e) {
    feedback.textContent = 'Network error — please try again.';
    feedback.className = 'text-xs font-body mt-2 text-red-600';
  }
  btn.disabled = false;
}
</script>
@endpush
