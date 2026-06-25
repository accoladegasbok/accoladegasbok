{{-- ── PASTE THIS BLOCK near the top of resources/views/admin/audit/show.blade.php,
     above your existing manual-count table. It adds scanner-gun support
     alongside whatever manual counting UI you already have — both work
     on the same audit_items rows, so staff can mix scanning and manual
     typing freely. ─────────────────────────────────────────────────── --}}

<div class="bg-white rounded-2xl border-2 border-gold shadow-sm p-5 mb-5">
  <h2 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">📷 Scan to Count</h2>
  <p class="text-xs text-gray-400 font-body mb-3">Scan each physical part's barcode as you find it on the shelf — each scan adds 1 to that item's count automatically.</p>
  <input type="text" id="scanInput" autocomplete="off" autofocus placeholder="Scan or type part barcode here..."
    class="w-full border-2 border-gold rounded-lg px-4 py-3 text-base font-mono focus:outline-none focus:ring-2 focus:ring-gold">
  <div id="scanFeedback" class="text-sm font-body mt-2 min-h-[20px]"></div>
</div>

<script>
const AUDIT_SESSION_ID = {{ $session->id }};
const scanInput = document.getElementById('scanInput');

scanInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleAuditScan(this.value.trim());
        this.value = '';
    }
});

async function handleAuditScan(code) {
    if (!code) return;
    const feedback = document.getElementById('scanFeedback');
    feedback.textContent = 'Looking up...';
    feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-gray-400';

    try {
        const res = await fetch(`/admin/audit/${AUDIT_SESSION_ID}/scan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ code }),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            feedback.textContent = '✕ ' + (data.error || 'Not found in this audit.');
            feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-red-600';
            return;
        }

        const diffText = data.discrepancy === 0 ? '' : (data.discrepancy > 0 ? ` (+${data.discrepancy} over expected)` : ` (${data.discrepancy} short of expected)`);
        feedback.textContent = `✓ ${data.part_name} — now counted: ${data.counted_qty} / expected ${data.expected_qty}${diffText}`;
        feedback.className = 'text-sm font-body mt-2 min-h-[20px] ' + (data.discrepancy === 0 ? 'text-green-600' : 'text-amber-600');

        // Update the row in your existing table if it has matching IDs —
        // adjust this selector to match however your table renders rows.
        const row = document.querySelector(`[data-audit-item-id="${data.item_id}"] [data-counted-qty]`);
        if (row) row.textContent = data.counted_qty;

    } catch (e) {
        feedback.textContent = 'Network error — try again.';
        feedback.className = 'text-sm font-body mt-2 min-h-[20px] text-red-600';
    }
}
</script>
