{{-- FILE: resources/views/admin/invoices/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Invoices/Receipts')
@section('page-title', 'Invoices/Receipts')
@section('page-sub', 'All issued invoices and receipts — online and in-store')

@section('header-actions')
<div class="flex gap-2">
  <a href="{{ route('admin.recycle-bin.index') }}"
     class="border border-gray-200 text-gray-500 font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">
    🗑 Recycle Bin
  </a>
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

{{-- Bulk action bar — admin/manager only. Hidden until at least one
     row is checked (see JS toggleBulkBar below). --}}
@if(in_array(session('staff_role'), ['admin','manager']))
<div id="bulkActionBar" class="hidden bg-navy text-white rounded-xl px-4 py-3 mb-4 flex items-center justify-between">
  <div class="text-sm font-body"><span id="bulkSelectedCount">0</span> selected</div>
  <div class="flex gap-2">
    <button onclick="bulkDeleteSelected()" class="bg-red-500 hover:bg-red-600 text-white text-xs font-display font-700 px-4 py-2 rounded-lg transition-colors">
      🗑 Delete Selected
    </button>
    <button onclick="clearBulkSelection()" class="border border-white border-opacity-30 text-white text-xs font-body px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors">
      Clear
    </button>
  </div>
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full">
    <thead class="bg-navy text-white">
      <tr>
        @if(in_array(session('staff_role'), ['admin','manager']))
        <th class="px-4 py-3 w-8">
          <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="accent-gold">
        </th>
        @endif
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Ref</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Customer</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Channel</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Location</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Amount</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Balance Due</th>
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
        @if(in_array(session('staff_role'), ['admin','manager']))
        <td class="px-4 py-3">
          <input type="checkbox" class="bulk-row-checkbox accent-gold" data-type="{{ $inv->type }}" data-id="{{ $inv->id }}" onchange="updateBulkBar()">
        </td>
        @endif
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
        @php
            $balDue = $inv->type === 'order'
                ? \App\Http\Controllers\Admin\OrderAdminController::paymentSummary($inv->id)['balanceDue']
                : \App\Http\Controllers\Admin\InvoiceController::invoicePaymentSummary($inv->id)['balanceDue'];
            $balFmt = $sym . ($inv->currency_code === 'NGN' ? number_format($balDue) : number_format($balDue, 2));
        @endphp
        <td class="px-4 py-3">
          @if($balDue > 0)
            <span class="badge badge-red">{{ $balFmt }}</span>
          @else
            <span class="badge badge-green">{{ $sym }}0</span>
          @endif
        </td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->payment_method ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $inv->staff ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y H:i') }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ $inv->url }}" target="_blank"
             class="text-xs font-body font-500 text-navy border border-navy rounded-lg px-3 py-1.5 hover:bg-navy hover:text-white transition-colors mr-1">
            🖨 View
          </a>
          <button onclick="deleteInvoiceRow('{{ $inv->type }}', {{ $inv->id }})"
             class="text-xs font-body font-500 text-red-500 border border-red-200 rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">
            🗑
          </button>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="10" class="px-4 py-12 text-center text-gray-400 font-body text-sm">
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

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const STAFF_ROLE = '{{ session("staff_role") }}';

// ── Single-row delete — now passes return_to so the redirect (fixed
// in InvoiceController::destroy() / OrderAdminController::destroy())
// brings the user back to THIS Invoices/Receipts page, instead of
// bouncing them to the Orders page whenever the deleted row happened
// to be an order under the hood. This was improvement #2.
function deleteInvoiceRow(type, id) {
    if (!confirm('Permanently delete this ' + (type === 'order' ? 'order' : 'invoice') + '? This cannot be undone from the normal interface.')) return;

    if (STAFF_ROLE === 'admin') {
        submitDeleteRow(type, id, null);
    } else {
        requestOverride('delete_' + type, `Delete ${type} #${id}`, function(approvedBy, role) {
            submitDeleteRow(type, id, `${approvedBy} (${role})`);
        });
    }
}

function submitDeleteRow(type, id, overrideToken) {
    const url = type === 'order' ? `/admin/orders/${id}` : `/admin/invoices/${id}`;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = `
        <input type="hidden" name="_token" value="${CSRF}">
        <input type="hidden" name="_method" value="DELETE">
        ${overrideToken ? `<input type="hidden" name="override_token" value="${overrideToken}">` : ''}
        <input type="hidden" name="return_to" value="${window.location.pathname + window.location.search}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// ── Bulk delete — admin/manager only (no PIN-override path for bulk;
// staff/supervisor still use the single-row delete above with PIN).
// Posts a list of {type, id} pairs to one endpoint that soft-deletes
// each in its correct table.
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.bulk-row-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.bulk-row-checkbox:checked');
    const bar = document.getElementById('bulkActionBar');
    const count = document.getElementById('bulkSelectedCount');
    if (!bar) return;
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.textContent = checked.length;
    } else {
        bar.classList.add('hidden');
    }
}

function clearBulkSelection() {
    document.querySelectorAll('.bulk-row-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAllCheckbox');
    if (selectAll) selectAll.checked = false;
    updateBulkBar();
}

async function bulkDeleteSelected() {
    const checked = document.querySelectorAll('.bulk-row-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm(`Permanently delete ${checked.length} selected item(s)? This cannot be undone from the normal interface.`)) return;

    const items = Array.from(checked).map(cb => ({ type: cb.dataset.type, id: parseInt(cb.dataset.id) }));

    try {
        const res = await fetch(`{{ route('admin.invoices.bulk-destroy') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ items }),
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Could not delete selected items.');
        }
    } catch (e) {
        alert('Network error — please try again.');
    }
}
</script>

@include('admin.override.modal-snippet')

@endsection
