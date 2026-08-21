<?php
// FILE: app/Http/Controllers/Admin/MediaGalleryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class MediaGalleryController extends Controller
{
    const MAX_VIDEO_MB = 50;
    const MAX_PHOTO_MB = 10;

    public function index()
    {
        $items = DB::table('media_gallery')->orderBy('display_order')->orderByDesc('created_at')->get();
        return view('admin.gallery.index', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'media_type' => 'required|in:photo,video',
            'title'      => 'nullable|string|max:150',
            'category'   => 'nullable|string|max:60',
            'file'       => $request->media_type === 'video'
                ? 'required|file|mimes:mp4,mov,webm|max:' . (self::MAX_VIDEO_MB * 1024)
                : 'required|image|max:' . (self::MAX_PHOTO_MB * 1024),
            'thumbnail'  => 'nullable|image|max:' . (self::MAX_PHOTO_MB * 1024),
        ]);

        $path = $request->file('file')->store('gallery', 'public');
        $thumbPath = $request->hasFile('thumbnail')
            ? $request->file('thumbnail')->store('gallery/thumbnails', 'public')
            : null;

        $maxOrder = DB::table('media_gallery')->max('display_order') ?? 0;

        DB::table('media_gallery')->insert([
            'media_type'            => $request->media_type,
            'title'                 => $request->title,
            'category'              => $request->category,
            'file_path'             => $path,
            'thumbnail_path'        => $thumbPath,
            'display_order'         => $maxOrder + 1,
            'is_active'             => true,
            'uploaded_by_staff_id'  => Session::get('staff_id'),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return redirect()->route('admin.gallery.index')->with('success', 'Added to gallery.');
    }

    public function toggle(int $id)
    {
        $item = DB::table('media_gallery')->where('id', $id)->first();
        abort_if(!$item, 404);

        DB::table('media_gallery')->where('id', $id)->update([
            'is_active'  => !$item->is_active,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'is_active' => !$item->is_active]);
    }

    // AJAX — drag-reorder from the admin grid
    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $position => $id) {
            DB::table('media_gallery')->where('id', $id)->update(['display_order' => $position]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $item = DB::table('media_gallery')->where('id', $id)->first();
        abort_if(!$item, 404);

        Storage::disk('public')->delete($item->file_path);
        if ($item->thumbnail_path) Storage::disk('public')->delete($item->thumbnail_path);

        DB::table('media_gallery')->where('id', $id)->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Removed from gallery.');
    }

    /**
     * Static helper — the ONE place the public homepage should call to
     * get active gallery items, ordered correctly. Keeps the "what
     * counts as visible" logic in one place instead of duplicated
     * across every view that might show the gallery.
     */
    public static function activeItems(?string $category = null)
    {
        $query = DB::table('media_gallery')->where('is_active', true)->orderBy('display_order');
        if ($category) $query->where('category', $category);
        return $query->get();
    }

    /**
     * GET /gallery-data — PUBLIC, unauthenticated JSON endpoint for
     * the homepage. Needed because home.blade.php is wrapped in
     * @verbatim (stops Blade parsing @media/@type CSS/JS as
     * directives), so it can't use a normal Blade @foreach loop —
     * fetches this via JS instead, same pattern the rest of that page
     * already uses for dynamic content.
     */
    public function publicJson()
    {
        $items = self::activeItems()->map(fn($item) => [
            'id'         => $item->id,
            'type'       => $item->media_type,
            'title'      => $item->title,
            'category'   => $item->category,
            'url'        => asset(config('media.prefix') . '/' . $item->file_path),
            'thumb_url'  => $item->thumbnail_path ? asset(config('media.prefix') . '/' . $item->thumbnail_path) : null,
        ]);

        return response()->json(['items' => $items]);
    }
}
