{{-- FILE: resources/views/admin/returns/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Log a Return')
@section('page-title','Log a Return')
@section('page-sub','For a customer return or an internal quality reject — the part goes on Hold until inspected')

@section('content')
<div class="max-w-2xl">
  <form method="POST" action="{{ route('admin.returns.store') }}" id="returnForm">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Return Type</h2>
      <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer">
          <input type="radio" name="return_type" value="customer" class="sr-only peer" checked>
          <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Customer Return</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Part was sold and customer brought it back</div>
          </div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="return_type" value="internal" class="sr-only peer">
          <div class="border-2 border-gray-200 rounded-xl p-3 peer-checked:border-gold peer-checked:bg-gold peer-checked:bg-opacity-10 transition-all">
            <div class="text-sm font-display font-700 text-navy">Internal Reject</div>
            <div class="text-xs text-gray-400 font-body mt-0.5">Quality issue found — never sold</div>
          </div>
        </label>
      </div>
    </div>

    {{-- Linked invoice — only relevant for customer returns --}}
    <div class="stat-card mb-5" id="invoiceSection">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Linked Invoice (optional)</h2>
      <p class="text-xs text-gray-400 font-body mb-4">Search by invoice number, customer name, or phone — or skip if you don't have the invoice handy.</p>
      <input type="text" id="invoiceSearchInput" placeholder="Search invoices..."
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      <div id="invoiceResults" class="mt-2 space-y-1 hidden"></div>
      <div id="selectedInvoice" class="mt-2 hidden bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 flex items-center justify-between">
        <span class="text-sm font-body text-blue-800" id="selectedInvoiceLabel"></span>
        <button type="button" onclick="clearInvoice()" class="text-xs text-blue-600 underline">Change</button>
      </div>
      <input type="hidden" name="invoice_id" id="invoiceIdInput">

      {{-- Items on that invoice, once selected --}}
      <div id="invoiceItemsSection" class="mt-3 hidden">
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Which item is being returned?</label>
        <select id="invoiceItemSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
          <option value="">Select item</option>
        </select>
        <input type="hidden" name="invoice_item_id" id="invoiceItemIdInput">
      </div>
    </div>

    {{-- FIXED: refund/cost amount was never captured anywhere on this
         form — auto-fills from the selected invoice item's real price
         (parts) or can be typed manually (e.g. for labour, or when no
         invoice is linked). --}}
    <div class="stat-card mb-5" id="refundSection">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Refund / Cost Amount</h2>
      <p class="text-xs text-gray-400 font-body mb-3">Auto-fills from the invoice item selected above — adjust if only partially refunding, or enter manually for labour/no-invoice cases.</p>
      <div class="relative max-w-xs">
        <span id="refundCurrencySymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₦</span>
        <input type="number" name="refund_amount_local" id="refundAmountInput" step="0.01" min="0"
          class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
          placeholder="0">
      </div>
    </div>

    {{-- Part being returned --}}
    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Part Being Returned *</h2>
      <p class="text-xs text-gray-400 font-body mb-4">Search by part code, part name, or donor VIN. (Auto-filled if you picked an invoice item above.)</p>
      <input type="text" id="partSearchInput" placeholder="Search parts..." required
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      <div id="partResults" class="mt-2 space-y-1 hidden"></div>
      <div id="selectedPart" class="mt-2 hidden bg-green-50 border border-green-100 rounded-lg px-3 py-2 flex items-center justify-between">
        <span class="text-sm font-body text-green-800" id="selectedPartLabel"></span>
        <button type="button" onclick="clearPart()" class="text-xs text-green-600 underline">Change</button>
      </div>
      <input type="hidden" name="part_id" id="partIdInput" required>
    </div>

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Reason for Return *</h2>
      <textarea name="reason" rows="3" required placeholder="e.g. Customer says it doesn't fit; wrong gear ratio; engine has a knock noise..."
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"></textarea>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Log Return — Place Part on Hold
      </button>
      <a href="{{ route('admin.returns.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>

@push('scripts')
<script>
// ── Return type toggle — hide invoice section for internal rejects ───────
document.querySelectorAll('input[name="return_type"]').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('invoiceSection').style.display = this.value === 'internal' ? 'none' : '';
        if (this.value === 'internal') clearInvoice();
    });
});

// ── Invoice search ──────────────────────────────────────────────────────────
let invoiceSearchTimer = null;
document.getElementById('invoiceSearchInput').addEventListener('input', function() {
    clearTimeout(invoiceSearchTimer);
    const q = this.value.trim();
    if (!q) { document.getElementById('invoiceResults').classList.add('hidden'); return; }
    invoiceSearchTimer = setTimeout(() => searchInvoices(q), 300);
});

async function searchInvoices(q) {
    const box = document.getElementById('invoiceResults');
    box.classList.remove('hidden');
    box.innerHTML = '<div class="text-xs text-gray-400 font-body">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.returns.search-invoices') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.invoices || data.invoices.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 font-body">No matches.</div>';
            return;
        }
        box.innerHTML = data.invoices.map(inv => `
            <button type="button" onclick='selectInvoice(${inv.id}, "${inv.invoice_no} — ${inv.customer_name}")'
                class="block w-full text-left text-sm font-body border border-gray-200 rounded-lg px-3 py-2 hover:border-gold transition-colors">
                <strong>${inv.invoice_no}</strong> — ${inv.customer_name} (${inv.customer_phone || 'no phone'})
            </button>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 font-body">Search failed.</div>';
    }
}

async function selectInvoice(id, label) {
    document.getElementById('invoiceIdInput').value = id;
    document.getElementById('selectedInvoiceLabel').textContent = label;
    document.getElementById('selectedInvoice').classList.remove('hidden');
    document.getElementById('invoiceResults').classList.add('hidden');
    document.getElementById('invoiceSearchInput').value = '';

    // Load items for this invoice
    const itemsSection = document.getElementById('invoiceItemsSection');
    const itemSelect = document.getElementById('invoiceItemSelect');
    itemSelect.innerHTML = '<option value="">Loading...</option>';
    itemsSection.classList.remove('hidden');
    try {
        const res = await fetch(`{{ route('admin.returns.invoice-items') }}?invoice_id=${id}`);
        const data = await res.json();
        // FIXED: currency symbol now matches the actual invoice's
        // currency instead of always showing ₦, and each option
        // carries its real line total for the refund autofill below.
        const symbols = { NGN: '₦', USD: '$', GHS: 'GH₵' };
        document.getElementById('refundCurrencySymbol').textContent = symbols[data.currency_code] || '₦';
        itemSelect.innerHTML = '<option value="">Select item</option>' +
            (data.items || []).map(it => `<option value="${it.id}" data-part-id="${it.part_id || ''}" data-label="${it.part_name} (${it.part_code})" data-line-total="${it.line_total_local || 0}">${it.part_name} — Qty ${it.qty}</option>`).join('');
    } catch (e) {
        itemSelect.innerHTML = '<option value="">Could not load items</option>';
    }
}

document.getElementById('invoiceItemSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('invoiceItemIdInput').value = this.value;
    const partId = opt ? opt.dataset.partId : '';
    if (partId) {
        document.getElementById('partIdInput').value = partId;
        document.getElementById('selectedPartLabel').textContent = opt.dataset.label;
        document.getElementById('selectedPart').classList.remove('hidden');
        document.getElementById('partSearchInput').value = '';
        document.getElementById('partResults').classList.add('hidden');
    }
    // Autofill refund amount from the invoice item's real line total —
    // staff can still adjust it if only partially refunding.
    if (opt && opt.dataset.lineTotal) {
        document.getElementById('refundAmountInput').value = parseFloat(opt.dataset.lineTotal).toFixed(2);
    }
});

function clearInvoice() {
    document.getElementById('invoiceIdInput').value = '';
    document.getElementById('invoiceItemIdInput').value = '';
    document.getElementById('selectedInvoice').classList.add('hidden');
    document.getElementById('invoiceItemsSection').classList.add('hidden');
}

// ── Part search ───────────────────────────────────────────────────────────
let partSearchTimer = null;
document.getElementById('partSearchInput').addEventListener('input', function() {
    clearTimeout(partSearchTimer);
    const q = this.value.trim();
    if (!q) { document.getElementById('partResults').classList.add('hidden'); return; }
    partSearchTimer = setTimeout(() => searchParts(q), 300);
});

async function searchParts(q) {
    const box = document.getElementById('partResults');
    box.classList.remove('hidden');
    box.innerHTML = '<div class="text-xs text-gray-400 font-body">Searching...</div>';
    try {
        const res = await fetch(`{{ route('admin.returns.search-parts') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.parts || data.parts.length === 0) {
            box.innerHTML = '<div class="text-xs text-gray-400 font-body">No matches.</div>';
            return;
        }
        box.innerHTML = data.parts.map(p => `
            <button type="button" onclick='selectPart(${p.id}, "${p.part_name} (${p.part_code}) — ${p.brand} ${p.model}, ${p.location}")'
                class="block w-full text-left text-sm font-body border border-gray-200 rounded-lg px-3 py-2 hover:border-gold transition-colors">
                <strong>${p.part_code}</strong> — ${p.part_name} (${p.brand} ${p.model}) · ${p.status}
            </button>
        `).join('');
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-500 font-body">Search failed.</div>';
    }
}

function selectPart(id, label) {
    document.getElementById('partIdInput').value = id;
    document.getElementById('selectedPartLabel').textContent = label;
    document.getElementById('selectedPart').classList.remove('hidden');
    document.getElementById('partResults').classList.add('hidden');
    document.getElementById('partSearchInput').value = '';
}

function clearPart() {
    document.getElementById('partIdInput').value = '';
    document.getElementById('selectedPart').classList.add('hidden');
}
</script>
@endpush
@endsection
