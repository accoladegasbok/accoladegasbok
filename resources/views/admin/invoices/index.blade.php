{{-- FILE: resources/views/admin/invoices/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-sub', 'All issued invoices and receipts')

@section('header-actions')
<a href="{{ route('admin.invoices.manual.create') }}"
   class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + New Invoice
</a>
@endsection

@section('content')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full">
    <thead class="bg-navy text-white">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Invoice No</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Customer</th>
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
        $rates   = ['NGN'=>1600,'GHS'=>15.5,'USD'=>1];
        $sym     = $symbols[$inv->currency_code] ?? '$';
        $rate    = $rates[$inv->currency_code] ?? 1;
        $amount  = $inv->subtotal_usd * $rate;
        $amtFmt  = $sym . ($inv->currency_code === 'NGN'
            ? number_format(round($amount))
            : number_format($amount, 2));
      @endphp
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 font-mono text-sm font-700 text-navy">{{ $inv->invoice_no }}</td>
        <td class="px-4 py-3">
          <div class="font-500 text-sm text-gray-800">{{ $inv->customer_name }}</div>
          @if($inv->customer_phone)<div class="text-xs text-gray-400">{{ $inv->customer_phone }}</div>@endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->location }}</td>
        <td class="px-4 py-3 font-display font-700 text-navy">{{ $amtFmt }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->payment_method }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->created_by }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y H:i') }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.invoices.show.manual', $inv->id) }}"
             target="_blank"
             class="text-xs font-body font-500 text-navy border border-navy rounded-lg px-3 py-1.5 hover:bg-navy hover:text-white transition-colors">
            🖨 Print
          </a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="px-4 py-12 text-center text-gray-400 font-body text-sm">
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
