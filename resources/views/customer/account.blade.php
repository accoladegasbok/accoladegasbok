@extends('layouts.app')
@section('title', 'My Account — Auto Zenith Parts')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">

  @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>@endif

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="font-display font-800 text-navy text-2xl">Hi, {{ explode(' ', $customer->name)[0] }}</h1>
      <p class="text-sm text-gray-500">Manage your details and view your order history.</p>
    </div>
    <form method="POST" action="{{ route('customer.logout') }}">
      @csrf
      <button type="submit" class="text-sm text-gray-500 hover:text-navy border border-gray-200 rounded-lg px-4 py-2">Log Out</button>
    </form>
  </div>

  {{-- ── Profile ─────────────────────────────────────────────── --}}
  <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Your Details</h2>
    <div class="grid grid-cols-2 gap-4 text-sm mb-5">
      <div>
        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Name</div>
        <div class="font-500 text-navy">{{ $customer->name }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Telegram</div>
        <div class="font-500 text-navy">
          @if($customer->telegram_chat_id) ✅ Linked
          @else <a href="{{ route('customer.telegram.link') }}" class="text-gold hover:underline">Link Telegram →</a>
          @endif
        </div>
      </div>
    </div>

    {{-- Email — change requires re-verification of the NEW email --}}
    <div class="border-t border-gray-100 pt-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider">Email</div>
          <div class="font-500 text-navy text-sm">{{ $customer->email }} @if($customer->email_verified_at)<span class="text-green-600 text-xs">✓ Verified</span>@endif</div>
        </div>
        <button type="button" onclick="document.getElementById('emailForm').classList.toggle('hidden')" class="text-xs text-navy border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50">Change</button>
      </div>
      <form id="emailForm" method="POST" action="{{ route('customer.email.change') }}" class="hidden flex gap-2 mt-2">
        @csrf
        <input type="email" name="email" placeholder="New email address" required class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        <button type="submit" class="bg-navy text-white text-xs font-600 px-4 py-2 rounded-lg">Send Code</button>
      </form>
    </div>

    {{-- Phone — same pattern --}}
    <div class="border-t border-gray-100 pt-4">
      <div class="flex items-center justify-between mb-2">
        <div>
          <div class="text-xs text-gray-400 uppercase tracking-wider">Phone</div>
          <div class="font-500 text-navy text-sm">{{ $customer->phone }} @if($customer->phone_verified_at)<span class="text-green-600 text-xs">✓ Verified</span>@endif</div>
        </div>
        <button type="button" onclick="document.getElementById('phoneForm').classList.toggle('hidden')" class="text-xs text-navy border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50">Change</button>
      </div>
      <form id="phoneForm" method="POST" action="{{ route('customer.phone.change') }}" class="hidden flex gap-2 mt-2">
        @csrf
        <input type="text" name="phone" placeholder="New phone number" required class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
        <button type="submit" class="bg-navy text-white text-xs font-600 px-4 py-2 rounded-lg">Send Code</button>
      </form>
      <p class="text-[11px] text-gray-400 mt-1.5">The confirmation code will be sent to your verified email — SMS verification isn't available yet.</p>
    </div>
  </div>

  {{-- ── Order History ───────────────────────────────────────── --}}
  <div class="bg-white border border-gray-200 rounded-2xl p-6">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Order History</h2>

    @if($orders->isEmpty() && $invoices->isEmpty())
      <p class="text-sm text-gray-400">No orders yet — matched by phone number ({{ $customer->phone }}). If you've ordered before under a different number, contact us to link it.</p>
    @else
      <div class="space-y-2">
        @foreach($orders as $o)
        <div class="flex items-center justify-between border border-gray-100 rounded-lg px-4 py-3">
          <div>
            <div class="text-sm font-600 text-navy">{{ $o->order_ref }}</div>
            <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }} · {{ str_replace('_',' ', ucfirst($o->order_status)) }}</div>
          </div>
          <div class="text-sm font-700 text-navy">{{ $o->currency_code ?? 'NGN' }} {{ number_format($o->total_amount_local ?? 0, 2) }}</div>
        </div>
        @endforeach
        @foreach($invoices as $i)
        <div class="flex items-center justify-between border border-gray-100 rounded-lg px-4 py-3">
          <div>
            <div class="text-sm font-600 text-navy">{{ $i->invoice_no }}</div>
            <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($i->created_at)->format('d M Y') }} · Receipt</div>
          </div>
          <div class="text-sm font-700 text-navy">{{ $i->currency_code ?? 'NGN' }} {{ number_format($i->subtotal_local ?? 0, 2) }}</div>
        </div>
        @endforeach
      </div>
    @endif
  </div>

</div>
@endsection
