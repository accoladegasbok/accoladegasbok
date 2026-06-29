{{-- FILE: resources/views/admin/tabs/show.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $tab->tab_no)
@section('page-title', $tab->tab_no . ' — ' . $tab->customer_name)
@section('page-sub', $tab->location . ' · ' . ucfirst($tab->status))

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  <div class="lg:col-span-2 space-y-4">

    @if($tab->status === 'open')
    <div class="bg-white rounded-2xl border-2 border-gold shadow-sm p-4">
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Add a Part or Service to This Tab</label>
      <input type="text" id="tabSearchInput" oninput="searchTabItems()" placeholder="Search inventory or services..."
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
      <div id="tabSearchResults" class="mt-2 space-y-1.5 max-h-64 overflow-y-auto"></div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide">Items on This Tab ({{ $items->count() }})</h2>
      </div>
      <table class="w-full text-sm font-body">
        <tbody>
          @forelse($items as $item)
          <tr class="border-b border-gray-50 last:border-0">
            <td class="px-4 py-3">
              <div class="font-500 text-navy">{{ $item->item_name }} {{ $item->item_type === 'service' ? '⚙' : '' }}</div>
              <div class="text-xs text-gray-400">{{ $item->item_code }} × {{ $item->qty }}</div>
            </td>
            <td class="px-4 py-3 text-right font-700 text-navy">
              {{ $currency['symbol'] }}{{ $item->currency_code === 'NGN' ? number_format($item->unit_price_local * $item->qty) : number_format($item->unit_price_local * $item->qty, 2) }}
            </td>
            @if($tab->status === 'open')
            <td class="px-4 py-3 text-right">
              <form method="POST" action="{{ route('admin.tabs.items.remove', [$tab->id, $item->id]) }}" onsubmit="return confirm('Remove this item from the tab?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕ Remove</button>
              </form>
            </td>
            @endif
          </tr>
          @empty
          <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">No items yet — search above to add parts or services.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
      <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Customer</div>
      <div class="font-700 text-navy">{{ $tab->customer_name }}</div>
      <div class="text-xs text-gray-500">{{ $tab->customer_phone }}</div>
      @if($tab->notes)<div class="text-xs text-gray-400 mt-2 italic">{{ $tab->notes }}</div>@endif
    </div>

    <div class="bg-navy rounded-2xl p-4 text-center">
      <div class="text-xs text-gray-300 uppercase tracking-wider mb-1">Running Total</div>
      <div class="font-display font-800 text-gold text-2xl">{{ $currency['symbol'] }}{{ $currency['code']==='NGN' ? number_format($total) : number_format($total,2) }}</div>
    </div>

    @if($tab->status === 'open')
    <form method="POST" action="{{ route('admin.tabs.close', $tab->id) }}" onsubmit="return confirm('Close this tab and create the final invoice? This deducts stock for all parts on the tab.')">
      @csrf
      <select name="payment_method" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white mb-3 focus:outline-none focus:border-gold">
        <option value="Cash">Cash</option>
        <option value="Card">Card</option>
        <option value="Bank Transfer">Bank Transfer</option>
        <option value="POS">POS</option>
      </select>
      <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-display font-700 text-sm py-3 rounded-xl transition-colors">
        ✓ Close Tab & Create Invoice
      </button>
    </form>
    <form method="POST" action="{{ route('admin.tabs.cancel', $tab->id) }}" onsubmit="return confirm('Cancel this tab? No invoice will be created.')" class="mt-2">
      @csrf
      <button type="submit" class="w-full border border-red-200 text-red-500 hover:bg-red-50 font-body font-500 text-sm py-2.5 rounded-xl transition-colors">Cancel Tab</button>
    </form>
    @elseif($tab->closed_invoice_id)
    <a href="{{ route('admin.invoices.show.manual', $tab->closed_invoice_id) }}" class="block w-full text-center bg-navy text-white font-display font-700 text-sm py-3 rounded-xl hover:bg-navy-light transition-colors">
      View Final Invoice →
    </a>
    @endif
  </div>
</div>

<script>
let tabSearchTimer = null;
function searchTabItems() {
    clearTimeout(tabSearchTimer);
    tabSearchTimer = setTimeout(doTabSearch, 300);
}

async function doTabSearch() {
    const q = document.getElementById('tabSearchInput').value;
    const box = document.getElementById('tabSearchResults');
    if (!q) { box.innerHTML = ''; return; }

    box.innerHTML = '<div class="text-xs text-gray-400">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.tabs.search', $tab->id) }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.items || data.items.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400">No matches.</div>';
            return;
        }
        box.innerHTML = data.items.map(item => `
            <form method="POST" action="{{ route('admin.tabs.items.add', $tab->id) }}" class="border border-gray-200 rounded-lg p-2 flex items-center justify-between gap-2 hover:border-gold transition-colors">
                @csrf
                <input type="hidden" name="item_type" value="${item.item_type}">
                <input type="hidden" name="ref_id" value="${item.id}">
                <div class="text-xs flex-1">
                    <strong class="text-navy">${item.part_name}</strong> ${item.item_type === 'service' ? '⚙' : ''} — ${item.part_code}
                    ${item.stock_qty !== null ? ' · Stock: ' + item.stock_qty : ''}
                </div>
                <input type="number" name="qty" value="1" min="1" class="w-14 border border-gray-200 rounded px-1 py-0.5 text-xs">
                <button type="submit" class="bg-gold text-navy text-xs font-700 px-2 py-1 rounded">Add</button>
            </form>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500">Search failed.</div>';
    }
}
</script>

@endsection
