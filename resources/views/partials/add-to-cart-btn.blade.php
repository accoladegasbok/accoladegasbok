{{--
  FILE: resources/views/partials/add-to-cart-btn.blade.php

  Drop this on any part listing card or detail page.
  Usage: @include('partials.add-to-cart-btn', ['part' => $part])

  Requires:
  - $part->id
  - $part->part_name
  - CSRF meta tag in <head>
--}}

<button
  class="add-to-cart-btn w-full flex items-center justify-center gap-2 text-xs font-body font-500
         bg-gold hover:bg-yellow-600 text-navy rounded-xl px-3 py-2.5 transition-colors"
  data-part-id="{{ $part->id }}"
  data-part-name="{{ $part->part_name }}"
  onclick="addToCart({{ $part->id }}, this)">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
  </svg>
  Add to cart
</button>

{{-- Include this script ONCE on the page (ideally in layouts/app.blade.php @stack('scripts')) --}}
@once
@push('scripts')
<script>
async function addToCart(partId, btn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Adding...';

  try {
    const res  = await fetch('/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ part_id: partId }),
    });
    const data = await res.json();

    if (data.success) {
      // Update cart badge(s) in nav
      document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = data.count;
        b.classList.remove('hidden');
      });

      // Change button to "Added" state
      btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Added to cart';
      btn.classList.remove('bg-gold','hover:bg-yellow-600','text-navy');
      btn.classList.add('bg-green-500','hover:bg-green-600','text-white');

      // Show toast
      showCartToast(data.message, data.count);
    } else {
      btn.innerHTML = orig;
      btn.disabled  = false;
      showCartToast(data.error || 'Could not add to cart.', null, true);
    }
  } catch (e) {
    btn.innerHTML = orig;
    btn.disabled  = false;
    showCartToast('Network error. Please try again.', null, true);
  }
}

function showCartToast(msg, count, isError = false) {
  // Remove existing toast
  document.getElementById('az-cart-toast')?.remove();

  const toast = document.createElement('div');
  toast.id    = 'az-cart-toast';
  toast.style.cssText = `
    position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
    z-index: 9999; padding: 10px 20px; border-radius: 12px; font-size: 13px;
    font-family: var(--font-sans, sans-serif); font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.15); animation: toastIn .25s ease;
    background: ${isError ? '#FEF2F2' : '#F0FDF4'};
    color: ${isError ? '#991B1B' : '#14532D'};
    border: 0.5px solid ${isError ? '#FCA5A5' : '#86EFAC'};
    white-space: nowrap;
  `;

  toast.innerHTML = isError
    ? `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>${msg}`
    : `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>${msg}
       ${count ? `<a href="/cart" style="text-decoration:underline;font-weight:600">View cart (${count})</a>` : ''}`;

  document.body.appendChild(toast);

  const style = document.createElement('style');
  style.textContent = '@keyframes toastIn { from { opacity:0; transform: translateX(-50%) translateY(10px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }';
  document.head.appendChild(style);

  setTimeout(() => toast.remove(), 4000);
}
</script>
@endpush
@endonce
