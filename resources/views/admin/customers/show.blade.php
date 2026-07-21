{{-- FILE: resources/views/admin/customers/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Customer History')
@section('page-title', $name)
@section('page-sub', $phone)
@section('header-actions')
<a href="{{ route('admin.customers.index') }}"
   class="border border-gray-200 text-gray-600 font-body font-500 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
  ← Back to Customers
</a>
@endsection
@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

{{-- NEW: editable profile — corrects the displayed name/phone/email/
     address without altering any historical order/invoice record.
     Those stay exactly as they were at time of sale. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
  <div class="flex items-center justify-between mb-1">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Profile</h2>
    <button type="button" onclick="toggleEditProfile()" id="editProfileBtn"
      class="text-xs font-body font-700 border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
      ✎ Edit Profile
    </button>
  </div>
  @if($override ?? null)
  <p class="text-xs text-amber-600 font-body">This profile has staff-corrected details — original auto-derived data is unaffected.</p>
  @endif

  <form method="POST" action="{{ route('admin.customers.update-profile', $phone) }}" id="editProfileForm" class="hidden mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
    @csrf
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Name</label>
      <input type="text" name="name" value="{{ old('name', $name) }}"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone <span class="font-normal text-gray-400">(display only — doesn't re-group past orders)</span></label>
      <input type="text" name="phone" value="{{ old('phone', $displayPhone ?? $phone) }}"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email', $email) }}"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Address</label>
      <input type="text" name="address" value="{{ old('address', $override->override_address ?? '') }}"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    </div>
    <div class="sm:col-span-2 flex gap-2">
      <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-5 py-2 rounded-xl hover:bg-yellow-500 transition-colors">Save</button>
      <button type="button" onclick="toggleEditProfile()" class="text-xs text-gray-400 px-3 hover:text-gray-600">Cancel</button>
    </div>
  </form>
</div>

{{-- NEW: quick-message — email/WhatsApp directly from the profile,
     no invoice/order context needed. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
  <div class="flex items-center justify-between mb-1">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Message This Customer</h2>
    <button type="button" onclick="toggleSendMessage()" id="sendMessageBtn"
      class="text-xs font-body font-700 border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
      ✉ Send Message
    </button>
  </div>
  <form method="POST" action="{{ route('admin.customers.send-message', $phone) }}" id="sendMessageForm" class="hidden mt-3 space-y-2">
    @csrf
    <input type="text" name="subject" placeholder="Subject" required
      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    <textarea name="message" rows="3" placeholder="Message..." required
      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold"></textarea>
    <div class="flex gap-2">
      <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-5 py-2 rounded-xl hover:bg-yellow-500 transition-colors">Send</button>
      <button type="button" onclick="toggleSendMessage()" class="text-xs text-gray-400 px-3 hover:text-gray-600">Cancel</button>
    </div>
  </form>
  @if(session('whatsapp_reminder_link'))
  <a href="{{ session('whatsapp_reminder_link') }}" target="_blank"
     class="block text-center mt-3 border border-green-300 bg-green-50 text-green-700 font-body font-500 text-xs py-2.5 rounded-xl hover:bg-green-100 transition-colors">
    💬 Message via WhatsApp (opens pre-filled — tap Send to actually deliver it)
  </a>
  @endif
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Spent</div>
    <div class="font-display font-700 text-navy text-2xl">${{ number_format($totalSpent, 2) }}</div>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Transactions</div>
    <div class="font-display font-700 text-navy text-2xl">{{ $totalCount }}</div>
  </div>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Contact</div>
    <div class="font-500 text-navy text-sm">{{ $email ?: 'No email on file' }}</div>
    <a href="https://wa.me/{{ $phone }}" target="_blank" class="text-green-600 hover:text-green-700 text-xs font-500">
      Message on WhatsApp →
    </a>
  </div>
</div>

{{-- Most purchased items --}}
@if($topItems->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-4">Most Purchased Items</h2>
  <div class="flex flex-wrap gap-2">
    @foreach($topItems as $item)
    <span class="bg-gray-50 border border-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">
      {{ $item->part_name }} <span class="text-gray-400">×{{ $item->count }}</span>
    </span>
    @endforeach
  </div>
</div>
@endif

{{-- Online orders --}}
@if($orders->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Online Orders ({{ $orders->count() }})</h2>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-gray-100">
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Order Ref</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
        <th class="px-5 py-2.5"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $o)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-5 py-3 font-mono text-navy text-xs">{{ $o->order_ref }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($o->created_at)->format('M j, Y') }}</td>
        <td class="px-5 py-3">
          <span class="badge badge-blue">{{ str_replace('_', ' ', $o->order_status) }}</span>
        </td>
        <td class="px-5 py-3 font-display font-700 text-navy text-xs">${{ number_format($o->total_amount_usd, 2) }}</td>
        <td class="px-5 py-3 text-right">
          <a href="{{ route('admin.orders.show', $o->id) }}" class="text-gold hover:text-yellow-600 text-xs font-500">View →</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- Manual invoices --}}
@if($invoices->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">In-Store / Phone Invoices ({{ $invoices->count() }})</h2>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-gray-100">
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Invoice No</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Amount</th>
        <th class="px-5 py-2.5"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoices as $inv)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-5 py-3 font-mono text-navy text-xs">{{ $inv->invoice_no }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($inv->created_at)->format('M j, Y') }}</td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ $inv->location }}</td>
        <td class="px-5 py-3 font-display font-700 text-navy text-xs">${{ number_format($inv->subtotal_usd, 2) }}</td>
        <td class="px-5 py-3 text-right">
          <a href="{{ route('admin.invoices.show.manual', $inv->id) }}" class="text-gold hover:text-yellow-600 text-xs font-500">View →</a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- NEW: return/credit history — both used and still-available
     credits, so staff have the full picture, not just what's
     redeemable right now. --}}
@if($returnHistory->count() > 0)
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mt-6">
  <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
    <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Return / Credit History ({{ $returnHistory->count() }})</h2>
  </div>
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-gray-100">
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Part</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-5 py-2.5 text-xs font-500 text-gray-400 uppercase tracking-wider">Credit</th>
      </tr>
    </thead>
    <tbody>
      @foreach($returnHistory as $r)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-5 py-3 text-navy text-xs">
          <div class="font-500">{{ $r->part_name }}</div>
          <div class="text-gray-400 font-mono">{{ $r->part_code }}</div>
        </td>
        <td class="px-5 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($r->created_at)->format('M j, Y') }}</td>
        <td class="px-5 py-3">
          @if($r->status !== 'resolved')
            <span class="badge badge-amber">{{ str_replace('_', ' ', ucfirst($r->status)) }}</span>
          @elseif($r->credit_applied_at)
            <span class="badge badge-gray">Used — Invoice #{{ $r->applied_to_invoice_id }}</span>
          @else
            <span class="badge badge-green">Available</span>
          @endif
        </td>
        <td class="px-5 py-3 font-display font-700 text-navy text-xs">
          {{ $r->refund_amount_local ? '₦' . number_format($r->refund_amount_local, 2) : '—' }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

{{-- NEW: staff notes on this customer profile --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mt-6">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">Staff Notes</h2>
  <form method="POST" action="{{ route('admin.customers.notes.add', $phone) }}" class="flex gap-2 mb-4">
    @csrf
    <input type="text" name="note" placeholder="e.g. prefers WhatsApp, always asks for trade pricing..." required
      class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
    <button type="submit" class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-opacity-90 transition-colors">Add Note</button>
  </form>
  @forelse($notes as $n)
  <div class="flex items-start justify-between border-b border-gray-50 py-2.5 last:border-0">
    <div>
      <div class="text-sm font-body text-gray-700">{{ $n->note }}</div>
      <div class="text-xs text-gray-400 mt-0.5">{{ $n->staff_name ?? 'Staff' }} · {{ \Carbon\Carbon::parse($n->created_at)->format('M j, Y g:i A') }}</div>
    </div>
    <form method="POST" action="{{ route('admin.customers.notes.destroy', $n->id) }}" onsubmit="return confirm('Remove this note?')">
      @csrf @method('DELETE')
      <button type="submit" class="text-xs text-red-400 hover:text-red-600">✕</button>
    </form>
  </div>
  @empty
  <p class="text-xs text-gray-400 font-body">No notes yet.</p>
  @endforelse
</div>

<script>
function toggleEditProfile() {
    document.getElementById('editProfileForm').classList.toggle('hidden');
}
function toggleSendMessage() {
    document.getElementById('sendMessageForm').classList.toggle('hidden');
}
</script>

@endsection
