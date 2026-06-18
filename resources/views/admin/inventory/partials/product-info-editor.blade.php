{{--
  FILE: resources/views/admin/inventory/partials/product-info-editor.blade.php

  Drop this inside the inventory edit form to allow admin to customise
  each product info bullet.
  Usage: @include('admin.inventory.partials.product-info-editor', ['part' => $part])
--}}

@php
  $stored = [];
  if (!empty($part->product_info)) {
      $stored = json_decode($part->product_info, true) ?? [];
  }
@endphp

<div class="stat-card mb-4">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">
    Product Information Block
  </h2>
  <p class="text-xs text-gray-400 font-body mb-4">
    These bullets appear on the customer-facing product page exactly like AllStarJDM.
    Leave blank to auto-generate from part data — only fill in to override.
  </p>

  <div class="space-y-4">

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Fitment <span class="text-gray-400 normal-case font-400">— auto: "2009-2015 Toyota Corolla"</span>
      </label>
      <input type="text" name="pi_fitment" value="{{ old('pi_fitment', $stored['fitment'] ?? '') }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
        placeholder="e.g. 2009-2015 Toyota Corolla">
    </div>

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Type <span class="text-gray-400 normal-case font-400">— auto: "Automatic Transmission · U341E · 13-pin"</span>
      </label>
      <input type="text" name="pi_type" value="{{ old('pi_type', $stored['type'] ?? '') }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
        placeholder="e.g. Automatic 4Speed">
    </div>

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Origin <span class="text-gray-400 normal-case font-400">— auto from origin_market field</span>
      </label>
      <input type="text" name="pi_origin" value="{{ old('pi_origin', $stored['origin'] ?? '') }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
        placeholder="e.g. JDM (Japanese Domestic Market)">
    </div>

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Warranty <span class="text-gray-400 normal-case font-400">— auto: 90 Days for engine/trans, 30 Days others</span>
      </label>
      <input type="text" name="pi_warranty" value="{{ old('pi_warranty', $stored['warranty'] ?? '') }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
        placeholder="e.g. 90 Days">
    </div>

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Included <span class="text-gray-400 normal-case font-400">— auto: "Complete Transmission (as pictured)"</span>
      </label>
      <input type="text" name="pi_included" value="{{ old('pi_included', $stored['included'] ?? '') }}"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
        placeholder="e.g. Complete Transmission (as pictured)">
    </div>

    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
        Notes <span class="text-gray-400 normal-case font-400">— auto: reuse sensors note for transmission</span>
      </label>
      <textarea name="pi_notes" rows="2"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none"
        placeholder="e.g. Reuse your original sensors and components for proper installation.">{{ old('pi_notes', $stored['notes'] ?? '') }}</textarea>
    </div>

    {{-- Extra custom bullets --}}
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-2">
        Extra Bullets (optional)
        <span class="text-gray-400 normal-case font-400">— add any additional product info line</span>
      </label>
      <div id="extraBullets" class="space-y-2">
        @foreach($stored['extras'] ?? [] as $i => $extra)
        <div class="flex gap-2 extra-bullet-row">
          <input type="text" name="pi_extra_label[]" value="{{ $extra['label'] }}" placeholder="Label (e.g. Speed)"
            class="w-36 border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400 flex-shrink-0">
          <input type="text" name="pi_extra_value[]" value="{{ $extra['value'] }}" placeholder="Value (e.g. 4-Speed)"
            class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
          <button type="button" onclick="this.closest('.extra-bullet-row').remove()"
            class="text-red-400 hover:text-red-600 px-2 flex-shrink-0">✕</button>
        </div>
        @endforeach
      </div>
      <button type="button" onclick="addExtraBullet()"
        class="mt-2 text-xs font-body font-500 text-blue-600 hover:text-blue-800 flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add extra bullet
      </button>
    </div>

  </div>

  {{-- Preview note --}}
  <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs font-body text-amber-700">
    💡 Blank fields auto-generate from part data. Only override if you need something different from the default.
  </div>
</div>

<script>
function addExtraBullet() {
  const container = document.getElementById('extraBullets');
  const div = document.createElement('div');
  div.className = 'flex gap-2 extra-bullet-row';
  div.innerHTML = `
    <input type="text" name="pi_extra_label[]" placeholder="Label (e.g. Speed)"
      class="w-36 border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400 flex-shrink-0">
    <input type="text" name="pi_extra_value[]" placeholder="Value (e.g. 4-Speed)"
      class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
    <button type="button" onclick="this.closest('.extra-bullet-row').remove()"
      class="text-red-400 hover:text-red-600 px-2 flex-shrink-0">✕</button>
  `;
  container.appendChild(div);
}
</script>
