{{-- FILE: resources/views/admin/invoices/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Invoices/Receipts')
@section('page-title', 'Invoices/Receipts')
@section('page-sub', 'All issued invoices and receipts — online and in-store')

@section('header-actions')
<div class="flex gap-2">
  <a href="{{ route('admin.invoices.service.create') }}"
     class="border border-gray-200 text-gray-600 font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    Quick Receipt
  </a>
  <a href="{{ route('admin.invoices.manual.create') }}"
     class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
    + New Invoice
  </a>
</div>
@endsection

@section('content')

<form method="GET" class="grid grid-cols-2 sm:grid-cols-6 gap-2 mb-4">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search ref, name, phone..."
    class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
  <input type="date" name="date_from" value="{{ request('date_from') }}"
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold" title="From date">
  <input type="date" name="date_to" value="{{ request('date_to') }}"
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold" title="To date">
  <select name="sort" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
    <option value="date_desc"   {{ request('sort','date_desc')==='date_desc'?'selected':'' }}>Newest First</option>
    <option value="date_asc"    {{ request('sort')==='date_asc'?'selected':'' }}>Oldest First</option>
    <option value="name_asc"    {{ request('sort')==='name_asc'?'selected':'' }}>Customer A–Z</option>
    <option value="name_desc"   {{ request('sort')==='name_desc'?'selected':'' }}>Customer Z–A</option>
    <option value="amount_desc" {{ request('sort')==='amount_desc'?'selected':'' }}>Amount High–Low</option>
    <option value="amount_asc"  {{ request('sort')==='amount_asc'?'selected':'' }}>Amount Low–High</option>
  </select>
  <button type="submit" class="bg-navy text-white font-display font-700 text-sm rounded-lg px-3 py-2 hover:bg-navy-light transition-colors">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full">
    <thead class="bg-navy text-white">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Ref</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Customer</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Channel</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Location</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Amount</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Payment</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Staff</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Date</th>
        <th class="px-4 py-3 text-xs font-display uppercase tracking-wide">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($invoices as $inv)
      @php
        $symbols = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'];
        $sym     = $symbols[$inv->currency_code] ?? '$';
        $amtFmt  = $sym . ($inv->currency_code === 'NGN'
            ? number_format(round($inv->amount_local))
            : number_format($inv->amount_local, 2));
      @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 font-mono text-sm font-700 text-navy">{{ $inv->ref }}</td>
        <td class="px-4 py-3">
          <div class="font-500 text-sm text-gray-800">{{ $inv->customer_name }}</div>
          @if($inv->customer_phone)<div class="text-xs text-gray-400">{{ $inv->customer_phone }}</div>@endif
        </td>
        <td class="px-4 py-3">
          <span class="badge
            @if($inv->channel === 'Online') badge-blue
            @elseif($inv->channel === 'Walk-in') badge-green
            @elseif($inv->channel === 'Phone') badge-amber
            @else badge-green @endif">
            {{ $inv->channel }}
          </span>
          <span class="badge {{ ($inv->doc_label ?? 'Invoice') === 'Receipt' ? 'badge-green' : 'badge-amber' }} ml-1">
            {{ $inv->doc_label ?? 'Invoice' }}
          </span>
          @if($inv->type === 'service')
            <span class="badge badge-amber ml-1">Service</span>
          @endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->location ?? '—' }}</td>
        <td class="px-4 py-3 font-display font-700 text-navy">{{ $amtFmt }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->payment_method ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->staff ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y H:i') }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ $inv->url }}" target="_blank"
             class="text-xs font-body font-500 text-navy border border-navy rounded-lg px-3 py-1.5 hover:bg-navy hover:text-white transition-colors">
            🖨 View
          </a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="9" class="px-4 py-12 text-center text-gray-400 font-body text-sm">
          No invoices yet. <a href="{{ route('admin.invoices.manual.create') }}" class="text-gold underline">Create your first invoice</a>.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
  <div class="px-4 py-3 border-t border-gray-100">
    {{ $invoices->links() }}
  </div>
</div>
@endsection
