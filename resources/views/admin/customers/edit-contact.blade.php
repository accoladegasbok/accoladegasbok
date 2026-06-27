{{-- FILE: resources/views/admin/customers/edit-contact.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit Contact')
@section('page-title','Edit ' . $contact->name)
@section('page-sub', $contact->contact_type)

@section('content')
<form method="POST" action="{{ route('admin.customers.contacts.update', $contact->id) }}" class="max-w-2xl">
@csrf @method('PUT')

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body mb-4">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-4">
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Name *</label>
    <input type="text" name="name" required value="{{ old('name', $contact->name) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Type *</label>
    <select name="contact_type" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
      @foreach($types as $t)<option value="{{ $t }}" {{ old('contact_type',$contact->contact_type)===$t?'selected':'' }}>{{ $t }}</option>@endforeach
    </select>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone *</label>
      <input type="text" name="phone" required value="{{ old('phone', $contact->phone) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">WhatsApp (if different)</label>
      <input type="text" name="whatsapp" value="{{ old('whatsapp', $contact->whatsapp) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email', $contact->email) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Address</label>
      <input type="text" name="address" value="{{ old('address', $contact->address) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes</label>
    <textarea name="notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">{{ old('notes', $contact->notes) }}</textarea>
  </div>
</div>

<div class="flex gap-3 justify-between mt-5 pb-8">
  <form method="POST" action="{{ route('admin.customers.contacts.destroy', $contact->id) }}" onsubmit="return confirm('Remove this contact?')">
    @csrf @method('DELETE')
    <button type="submit" class="text-xs font-body border border-red-200 text-red-500 hover:bg-red-50 px-4 py-2.5 rounded-xl transition-colors">Remove Contact</button>
  </form>
  <div class="flex gap-3">
    <a href="{{ route('admin.customers.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Save Changes</button>
  </div>
</div>
</form>
@endsection
