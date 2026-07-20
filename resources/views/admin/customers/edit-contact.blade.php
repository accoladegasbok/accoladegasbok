{{-- FILE: resources/views/admin/customers/edit-contact.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Edit Contact')
@section('page-title', 'Edit Contact')
@section('page-sub', $contact->name . ' · ' . $contact->contact_type)

@section('content')
<div class="max-w-2xl">

  <form method="POST" action="{{ route('admin.customers.contacts.update', $contact->id) }}">
    @csrf
    @method('PUT')

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Contact Details</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Name *</label>
          <input type="text" name="name" value="{{ old('name', $contact->name) }}" required
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Contact Type *</label>
          <select name="contact_type" required
                  class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            @foreach($types as $type)
            <option value="{{ $type }}" {{ old('contact_type', $contact->contact_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Phone *</label>
          <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}" required
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
                 placeholder="+234 915 568 8804">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            WhatsApp <span class="font-normal text-gray-300">(optional, if different from phone)</span>
          </label>
          <input type="text" name="whatsapp" value="{{ old('whatsapp', $contact->whatsapp) }}"
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Email <span class="font-normal text-gray-300">(optional)</span>
          </label>
          <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Address <span class="font-normal text-gray-300">(optional)</span>
          </label>
          <input type="text" name="address" value="{{ old('address', $contact->address) }}"
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Notes</label>
          <textarea name="notes" rows="3"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none"
                    placeholder="Any details worth remembering about this contact...">{{ old('notes', $contact->notes) }}</textarea>
        </div>

      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit"
              class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
      <a href="{{ route('admin.customers.index') }}"
         class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">
        Cancel
      </a>
    </div>

  </form>

  <div class="mt-5 stat-card border-red-100">
    <h3 class="text-xs font-body font-700 text-red-600 uppercase tracking-wider mb-2">Danger Zone</h3>
    <form method="POST" action="{{ route('admin.customers.contacts.destroy', $contact->id) }}"
          onsubmit="return confirm('Remove {{ $contact->name }} from contacts? This cannot be undone.');">
      @csrf
      @method('DELETE')
      <button type="submit" class="text-xs font-body font-500 text-red-600 border border-red-200 rounded-lg px-4 py-2 hover:bg-red-50 transition-colors">
        Remove Contact
      </button>
    </form>
  </div>

</div>
@endsection
