{{-- FILE: resources/views/admin/gallery/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Photo & Video Gallery')
@section('page-title', 'Homepage Gallery')
@section('page-sub', 'Brand and staff photos/videos shown on the public homepage — drag to reorder')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

{{-- Upload form --}}
<div class="stat-card mb-6">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Add Photo or Video</h2>
  <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" id="uploadForm">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Type *</label>
        <select name="media_type" id="mediaTypeSelect" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
          <option value="photo">Photo</option>
          <option value="video">Video</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
        <input type="text" name="category" placeholder="Brand, Staff, Shop Floor..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      </div>
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Title <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
        <input type="text" name="title" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
      </div>
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">File *</label>
        <input type="file" name="file" required accept="image/*,video/mp4,video/quicktime,video/webm" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
      </div>
    </div>
    <div id="thumbnailField" class="mt-3 hidden">
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Video Thumbnail <span class="text-gray-400 font-normal normal-case">(optional — shown before play)</span></label>
      <input type="file" name="thumbnail" accept="image/*" class="w-full sm:w-1/4 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
    </div>
    <button type="submit" class="mt-4 bg-gold text-navy font-display font-700 text-sm px-6 py-3 rounded-xl hover:bg-yellow-500 transition-colors">+ Add to Gallery</button>
    <p class="text-xs text-gray-400 font-body mt-2">Photos up to 10MB, videos up to 50MB (MP4/MOV/WebM).</p>
  </form>
</div>

{{-- Gallery grid --}}
<div class="stat-card">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Current Gallery ({{ $items->count() }})</h2>
  @if($items->isEmpty())
  <p class="text-sm text-gray-400 font-body">Nothing added yet — use the form above.</p>
  @else
  <div id="galleryGrid" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
    @foreach($items as $item)
    <div class="gallery-item border border-gray-200 rounded-xl overflow-hidden {{ !$item->is_active ? 'opacity-40' : '' }}" data-id="{{ $item->id }}" draggable="true">
      <div class="relative aspect-square bg-gray-100">
        @if($item->media_type === 'video')
          @if($item->thumbnail_path)
          <img src="{{ asset('storage/' . $item->thumbnail_path) }}" class="w-full h-full object-cover">
          @else
          <div class="w-full h-full flex items-center justify-center text-gray-400 text-2xl">🎬</div>
          @endif
          <span class="absolute top-1 right-1 text-[9px] bg-navy text-white px-1.5 py-0.5 rounded font-700">VIDEO</span>
        @else
          <img src="{{ asset('storage/' . $item->file_path) }}" class="w-full h-full object-cover">
        @endif
      </div>
      <div class="p-2">
        @if($item->category)<div class="text-[10px] text-gold font-700 uppercase">{{ $item->category }}</div>@endif
        @if($item->title)<div class="text-xs text-navy font-600 truncate">{{ $item->title }}</div>@endif
        <div class="flex items-center justify-between mt-1.5">
          <button type="button" onclick="toggleActive({{ $item->id }}, this)" class="text-[10px] font-600 {{ $item->is_active ? 'text-green-600' : 'text-gray-400' }}">
            {{ $item->is_active ? '● Live' : '○ Hidden' }}
          </button>
          <form method="POST" action="{{ route('admin.gallery.destroy', $item->id) }}" onsubmit="return confirm('Remove this from the gallery?')">
            @csrf @method('DELETE')
            <button class="text-[10px] text-red-400 hover:text-red-600">✕ Remove</button>
          </form>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  <p class="text-xs text-gray-400 font-body mt-3">Drag items to reorder how they appear on the homepage.</p>
  @endif
</div>

<script>
document.getElementById('mediaTypeSelect').addEventListener('change', function() {
    document.getElementById('thumbnailField').classList.toggle('hidden', this.value !== 'video');
});

async function toggleActive(id, btn) {
    try {
        const res = await fetch(`/admin/gallery/${id}/toggle`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = data.is_active ? '● Live' : '○ Hidden';
            btn.className = 'text-[10px] font-600 ' + (data.is_active ? 'text-green-600' : 'text-gray-400');
            btn.closest('.gallery-item').classList.toggle('opacity-40', !data.is_active);
        }
    } catch (e) { alert('Could not update — try again.'); }
}

// ── Drag-to-reorder ──────────────────────────────────────────────
let dragged = null;
document.querySelectorAll('.gallery-item').forEach(el => {
    el.addEventListener('dragstart', () => { dragged = el; el.style.opacity = '0.4'; });
    el.addEventListener('dragend', () => { el.style.opacity = ''; saveOrder(); });
    el.addEventListener('dragover', e => e.preventDefault());
    el.addEventListener('drop', function(e) {
        e.preventDefault();
        if (dragged && dragged !== this) {
            this.parentNode.insertBefore(dragged, this.nextSibling);
        }
    });
});

async function saveOrder() {
    const ids = Array.from(document.querySelectorAll('.gallery-item')).map(el => el.dataset.id);
    try {
        await fetch(`{{ route('admin.gallery.reorder') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ order: ids }),
        });
    } catch (e) {}
}
</script>
@endsection
