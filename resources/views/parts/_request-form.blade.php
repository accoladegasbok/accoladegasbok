{{-- FILE: resources/views/parts/_request-form.blade.php
     "Request this part" form. Include with:
       $requestVehicle  text shown/saved as the vehicle (optional)
       $requestPart     text shown/saved as the part(s)  (optional)
       $requestPartId   the sold/unavailable part the customer looked at (optional)
       $requestSource   'search' | 'part_page'
       $requestOpen     true to show the form expanded --}}
@php
    $requestVehicle = $requestVehicle ?? '';
    $requestPart    = $requestPart ?? '';
    $requestPartId  = $requestPartId ?? null;
    $requestSource  = $requestSource ?? 'search';
    $requestOpen    = $requestOpen ?? false;
    $bag            = $errors->getBag('partRequest');
@endphp
<div id="request-part" class="mt-8">
  @if(session('part_request_sent'))
    <div class="rounded-xl border border-green-200 bg-green-50 text-green-800 text-sm font-body px-4 py-3">
      {{ session('part_request_sent') }}
    </div>
  @else
  <details class="bg-white border border-gray-200 rounded-2xl shadow-sm" {{ ($requestOpen || $bag->any()) ? 'open' : '' }}>
    <summary class="cursor-pointer select-none px-5 py-4 font-display font-700 text-navy text-sm tracking-wide uppercase">
      Can't find it? Request this part
    </summary>
    <form method="POST" action="{{ route('parts.request.store') }}" class="px-5 pb-5 pt-1">
      @csrf
      <input type="hidden" name="source" value="{{ $requestSource }}">
      @if($requestPartId)<input type="hidden" name="part_id" value="{{ $requestPartId }}">@endif
      {{-- Honeypot: hidden from people, bots fill it in --}}
      <div style="position:absolute; left:-9999px;" aria-hidden="true">
        <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <p class="text-xs text-gray-500 font-body mb-4">Tell us what you need. We search our yards and our suppliers and contact you as soon as we find it.</p>

      @if($bag->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs font-body px-3 py-2 mb-3">
          @foreach($bag->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
      @endif

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Your name *</label>
          <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone / WhatsApp *</label>
          <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="40"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email (optional)</label>
          <input type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="150"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
        </div>
        <div>
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Where do you need it?</label>
          <select name="region" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
            <option value="Lagos / West Africa" {{ old('region') === 'Lagos / West Africa' ? 'selected' : '' }}>Lagos / West Africa</option>
            <option value="USA" {{ old('region') === 'USA' ? 'selected' : '' }}>USA</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Vehicle (year, make, model)</label>
          <input type="text" name="vehicle_text" value="{{ old('vehicle_text', $requestVehicle) }}" maxlength="255" placeholder="e.g. 2008 Honda Accord"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Part(s) needed *</label>
          <input type="text" name="part_text" value="{{ old('part_text', $requestPart) }}" required maxlength="255" placeholder="e.g. Alternator"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Anything else we should know?</label>
          <textarea name="notes" rows="2" maxlength="1000" placeholder="Engine size, VIN, left or right side..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">{{ old('notes') }}</textarea>
        </div>
      </div>
      <button type="submit" class="mt-4 bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-6 py-3 rounded-xl tracking-wide transition-colors">
        SEND MY REQUEST
      </button>
    </form>
  </details>
  @endif
</div>
