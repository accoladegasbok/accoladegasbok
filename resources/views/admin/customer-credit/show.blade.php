{{-- FILE: resources/views/admin/customer-credit/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Customer Credit')
@section('page-title', 'Credit — ' . ($name ?: ('phone …' . substr($phoneKey, -6))))
@section('page-sub', 'Every credit and every use, newest first')

@php
  $sym = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
  $labels = ['overpayment' => 'Overpayment', 'return_credit' => 'Return credit', 'used_invoice' => 'Used on invoice', 'used_order' => 'Used on order',
             'payout_cash' => 'Paid out — cash', 'payout_transfer' => 'Paid out — transfer', 'adjustment' => 'Adjustment'];
@endphp

@section('content')
@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<a href="{{ route('admin.customer-credit.index') }}" class="text-xs text-gray-400 hover:text-navy">← All customers</a>

<div class="flex flex-wrap gap-3 my-4">
  @foreach($balances as $code => $bal)
  <div class="bg-white border border-gray-200 rounded-xl px-5 py-3">
    <div class="text-xs text-gray-400 uppercase tracking-wider">Available ({{ $code }})</div>
    <div class="font-display font-800 text-navy text-2xl">{{ $sym[$code] ?? '' }}{{ $code === 'NGN' ? number_format($bal) : number_format($bal, 2) }}</div>
  </div>
  @endforeach
</div>

@if($canManage && $balances->filter(fn($b) => $b > 0)->count())
<details class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-5">
  <summary class="cursor-pointer px-5 py-4 font-display font-700 text-navy text-sm uppercase tracking-wide">Pay credit back to the customer (cash or transfer)</summary>
  <form method="POST" action="{{ route('admin.customer-credit.payout', $phoneKey) }}" class="px-5 pb-5 grid grid-cols-1 sm:grid-cols-5 gap-3" onsubmit="return confirm('Pay this credit out now? This is logged and cannot be undone.')">
    @csrf
    <select name="currency" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
      @foreach($balances->filter(fn($b) => $b > 0) as $code => $bal)<option value="{{ $code }}">{{ $code }} (max {{ $code === 'NGN' ? number_format($bal) : number_format($bal, 2) }})</option>@endforeach
    </select>
    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="Amount" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <select name="method" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white"><option value="cash">Cash</option><option value="transfer">Bank transfer</option></select>
    <input type="text" name="reference" placeholder="Transfer reference (optional)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <input type="text" name="notes" placeholder="Note (optional)" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
    <button class="sm:col-span-5 sm:w-auto justify-self-start bg-navy text-white font-display font-700 text-sm px-5 py-2 rounded-lg">Pay out</button>
  </form>
</details>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
      <tr><th class="text-left px-4 py-3">Date</th><th class="text-left px-4 py-3">What</th><th class="text-left px-4 py-3">Reference</th><th class="text-right px-4 py-3">Amount</th><th class="text-left px-4 py-3">By</th><th class="text-left px-4 py-3">Note</th></tr>
    </thead>
    <tbody>
      @foreach($entries as $e)
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2.5 text-gray-500">{{ \Carbon\Carbon::parse($e->created_at)->format('d M Y, H:i') }}</td>
        <td class="px-4 py-2.5">{{ $labels[$e->entry_type] ?? $e->entry_type }}</td>
        <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $e->reference ?: '—' }}</td>
        <td class="px-4 py-2.5 text-right font-display font-700 {{ $e->amount_local >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $e->amount_local >= 0 ? '+' : '−' }}{{ $sym[$e->currency_code] ?? '' }}{{ $e->currency_code === 'NGN' ? number_format(abs($e->amount_local)) : number_format(abs($e->amount_local), 2) }}</td>
        <td class="px-4 py-2.5 text-gray-500">{{ $e->staff_name ?: '—' }}</td>
        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $e->notes }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
