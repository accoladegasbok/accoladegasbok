{{-- FILE: resources/views/admin/override/set-pin.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Set Override PIN')
@section('page-title','My Override PIN')
@section('page-sub','Personal 4-digit PIN used to approve sensitive actions \u2014 separate from your login password')

@section('content')
<div class="max-w-md">
  <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm font-body mb-5">
    This PIN is yours alone. Nobody — including Admin — can see it once set; only you. If you forget it, ask an Admin to clear it so you can set a new one. Every time you use this PIN to approve something, it's logged with your name.
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">New 4-Digit PIN</label>
    <input type="text" id="newPin" inputmode="numeric" maxlength="4" placeholder="••••"
      class="w-full border-2 border-gold rounded-lg px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none mb-3">
    <button onclick="savePin()" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">
      Save PIN
    </button>
    <div id="pinFeedback" class="text-sm font-body mt-3 text-center"></div>
  </div>
</div>

<script>
async function savePin() {
    const pin = document.getElementById('newPin').value;
    const feedback = document.getElementById('pinFeedback');
    if (!/^\d{4}$/.test(pin)) {
        feedback.textContent = 'PIN must be exactly 4 digits.';
        feedback.className = 'text-sm font-body mt-3 text-center text-red-600';
        return;
    }
    const res = await fetch(`{{ route('admin.override.set-own-pin') }}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ pin }),
    });
    const data = await res.json();
    if (data.success) {
        feedback.textContent = '✓ PIN saved.';
        feedback.className = 'text-sm font-body mt-3 text-center text-green-600';
        document.getElementById('newPin').value = '';
    } else {
        feedback.textContent = data.error || 'Could not save PIN.';
        feedback.className = 'text-sm font-body mt-3 text-center text-red-600';
    }
}
</script>
@endsection
