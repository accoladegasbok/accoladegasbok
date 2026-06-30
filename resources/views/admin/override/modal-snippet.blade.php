{{-- FILE: resources/views/admin/override/modal-snippet.blade.php
     Reusable Supervisor-approval PIN modal. Include with:
     @include('admin.override.modal-snippet')
     anywhere an override-protected action is needed: removing a cart
     item mid-sale, deleting an invoice/order/inventory part, editing
     a locked price, etc. ── --}}

<div id="overridePinModal" class="hidden fixed inset-0 z-[70] bg-black bg-opacity-50 items-center justify-center px-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
    <h3 class="font-display font-700 text-navy text-lg tracking-wide mb-1">Supervisor Approval Required</h3>
    <p id="overrideContextText" class="text-sm text-gray-500 font-body mb-4"></p>
    <input type="text" id="overridePinInput" inputmode="numeric" maxlength="4" autocomplete="off"
      placeholder="Enter 4-digit PIN" class="w-full border-2 border-gold rounded-lg px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none">
    <div id="overrideError" class="text-xs text-red-600 font-body mt-2 min-h-[16px]"></div>
    <div class="flex gap-2 mt-4">
      <button type="button" onclick="closeOverrideModal()" class="flex-1 border border-gray-200 text-gray-600 font-body font-500 text-sm py-2.5 rounded-xl hover:bg-gray-50">Cancel</button>
      <button type="button" onclick="submitOverridePin()" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-2.5 rounded-xl hover:bg-yellow-500">Approve</button>
    </div>
  </div>
</div>

<script>
// ── Override PIN system ──────────────────────────────────────────────
// Call requestOverride('action_name', 'human-readable context', callback)
// from anywhere on the page. The callback only fires if a valid
// Supervisor/Manager/Admin PIN was entered — every attempt is logged
// server-side regardless of outcome.
//
// NOTE: this snippet defines requestOverride/closeOverrideModal/
// submitOverridePin globally. If a page already defines its own
// version of these (e.g. POS, Place Order), don't also @@include this
// partial on that same page — pick one source to avoid duplicate
// function declarations.
let _overrideCallback = null;
let _overrideAction = null;
let _overrideContext = null;

function requestOverride(action, context, callback) {
    _overrideAction = action;
    _overrideContext = context;
    _overrideCallback = callback;
    document.getElementById('overrideContextText').textContent = context;
    document.getElementById('overridePinInput').value = '';
    document.getElementById('overrideError').textContent = '';
    const modal = document.getElementById('overridePinModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('overridePinInput').focus();
}

function closeOverrideModal() {
    const modal = document.getElementById('overridePinModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    _overrideCallback = null;
}

async function submitOverridePin() {
    const pin = document.getElementById('overridePinInput').value;
    const errorBox = document.getElementById('overrideError');

    if (pin.length !== 4) {
        errorBox.textContent = 'Enter a 4-digit PIN.';
        return;
    }

    try {
        const res = await fetch('/admin/override/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ pin, action: _overrideAction, context: _overrideContext }),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            errorBox.textContent = data.error || 'Invalid PIN.';
            document.getElementById('overridePinInput').value = '';
            return;
        }

        const cb = _overrideCallback;
        closeOverrideModal();
        if (cb) cb(data.approved_by, data.role);

    } catch (e) {
        errorBox.textContent = 'Network error — try again.';
    }
}

document.getElementById('overridePinInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') submitOverridePin();
});
</script>
