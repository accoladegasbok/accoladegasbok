<?php

/**
 * ═══════════════════════════════════════════════════════════
 * THREE FIXES — add these to your existing files
 * ═══════════════════════════════════════════════════════════
 *
 *
 * ╔══════════════════════════════════════════════════════════╗
 * ║  FIX A — Room required, bin optional (restore original) ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * In resources/views/admin/harvest/checklist.blade.php,
 * the JS submit guard already enforces "a room or bin must be
 * selected" via the bins[] check. The issue is the dropdown
 * renders "room:ROOM NAME" as the placeholder value.
 *
 * In loadHarvestRooms(), ensure the room-only option always
 * appears FIRST in each optgroup so it's the default:
 *
 *   optionsHtml += `<optgroup label="${roomName}">`;
 *   // Room-only option FIRST (always available):
 *   optionsHtml += `<option value="room:${roomName}">📍 ${roomName} — room only (no bin yet)</option>`;
 *   // Then specific bins within the room:
 *   rooms[roomName].forEach(b => {
 *       optionsHtml += `<option value="${b.id}">${b.full_bin_code}</option>`;
 *   });
 *   optionsHtml += `</optgroup>`;
 *
 * In the JS submit guard (before form submits), enforce:
 *
 *   function validateHarvestForm() {
 *       const checkedParts = document.querySelectorAll('.part-checkbox:checked:not(:disabled)');
 *       const missingRoom = [];
 *       checkedParts.forEach(chk => {
 *           const key = chk.dataset.key;
 *           const binSel = document.querySelector(`select[name="bins[${key}]"]`);
 *           if (!binSel || !binSel.value) missingRoom.push(key);
 *       });
 *       if (missingRoom.length > 0) {
 *           alert('Every ticked part needs at least a ROOM selected before saving.\n'
 *               + 'You can select a specific bin too if it\'s ready, or just pick the room for now.');
 *           return false;
 *       }
 *       return true;
 *   }
 *
 * And on the form submit button:
 *   onclick="return validateHarvestForm()"
 *
 * The server-side saveParts() already handles the room: prefix
 * by setting storage_shelf_id = null and bin_location = "ROOM X — bin not yet assigned"
 *
 *
 * ╔══════════════════════════════════════════════════════════╗
 * ║  FIX B — Default coming-soon image for parts            ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Place a default image at: public/images/coming-soon.jpg
 * (or use the existing one if you already have it — check with
 * ls public/images/ on your server)
 *
 * Wherever you render a part's photo (inventory list, part detail,
 * POS search results, harvest complete screen), replace the img src
 * with this Blade helper pattern:
 *
 *   @php
 *     $photos = is_string($part->photos) ? json_decode($part->photos, true) : ($part->photos ?? []);
 *     $firstPhoto = (!empty($photos) && is_array($photos)) ? $photos[0] : null;
 *   @endphp
 *
 *   <img src="{{ $firstPhoto
 *       ? asset(config('media.prefix', 'storage') . '/' . $firstPhoto)
 *       : asset('images/coming-soon.jpg') }}"
 *        alt="{{ $part->part_name }}"
 *        onerror="this.src='{{ asset('images/coming-soon.jpg') }}'">
 *
 * The onerror handler catches broken/missing image paths too,
 * so even if a photo path is stored but the file was deleted,
 * the coming-soon banner shows instead of a broken image icon.
 *
 * For the harvest checklist specifically — the photo upload input
 * shows a preview; once selected show the preview, otherwise show
 * the coming-soon banner as a placeholder:
 *
 *   <div class="photo-preview-wrap w-16 h-12 rounded overflow-hidden border border-dashed border-slate-600">
 *     <img id="preview-{{ $part['key'] }}"
 *          src="{{ asset('images/coming-soon.jpg') }}"
 *          class="w-full h-full object-cover"
 *          alt="No photo yet">
 *   </div>
 *
 * And in JS when a file is selected:
 *
 *   document.addEventListener('change', function(e) {
 *     if (e.target.matches('.harvest-photo-input')) {
 *       const key = e.target.dataset.partKey;
 *       const preview = document.getElementById('preview-' + key);
 *       if (preview && e.target.files[0]) {
 *         preview.src = URL.createObjectURL(e.target.files[0]);
 *       }
 *     }
 *   });
 *
 *
 * ╔══════════════════════════════════════════════════════════╗
 * ║  FIX C — Barcode label route + controller               ║
 * ╚══════════════════════════════════════════════════════════╝
 */

// ── Add to InventoryController (or a new BarcodeController) ──────────────

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodeController extends \App\Http\Controllers\Controller
{
    /**
     * GET /admin/inventory/barcode-label?ids=1,2,3&size=large
     *
     * ids  = comma-separated parts_inventory IDs
     * size = 'small' (2x1 barcode-only) | 'large' (4x6 with product info)
     *
     * Add to routes/web.php:
     *   Route::get('/admin/inventory/barcode-label',
     *       [\App\Http\Controllers\Admin\BarcodeController::class, 'show'])
     *       ->name('admin.inventory.barcode-label');
     *
     * On the inventory list, add a "🏷 Label" button per row:
     *   <a href="{{ route('admin.inventory.barcode-label', ['ids' => $part->id, 'size' => 'large']) }}"
     *      target="_blank"
     *      class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50">
     *       🏷 Label
     *   </a>
     *
     * For batch printing from the bulk-select checkbox system:
     *   <a href="#" onclick="printSelectedLabels()" class="...">🏷 Print Labels</a>
     *   <script>
     *   function printSelectedLabels(size = 'large') {
     *     const ids = Array.from(document.querySelectorAll('.bulk-row-checkbox:checked'))
     *                      .map(cb => cb.dataset.id).join(',');
     *     if (!ids) { alert('Select at least one part first'); return; }
     *     window.open(`/admin/inventory/barcode-label?ids=${ids}&size=${size}`, '_blank');
     *   }
     *   </script>
     */
    public function show(Request $request)
    {
        $ids  = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        $size = in_array($request->get('size', 'large'), ['small', 'large'])
            ? $request->get('size', 'large')
            : 'large';

        if (empty($ids)) abort(400, 'No part IDs provided.');

        $parts = DB::table('parts_inventory')
            ->whereIn('id', $ids)
            ->select(
                'id', 'part_code', 'part_name', 'part_category',
                'brand', 'model', 'year_from', 'year_to',
                'engine_code_oem', 'transmission_code_oem', 'pin_count',
                'condition_grade', 'conditions_and_options',
                'price_local', 'price_wholesale', 'currency_code',
                'bin_location', 'location', 'donor_vin',
                'description', 'photos',
                'is_major_component', 'legal_trace_required'
            )
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')') // preserve selection order
            ->get();

        if ($parts->isEmpty()) abort(404, 'No matching parts found.');

        return view('admin.inventory.barcode-label', compact('parts', 'size'));
    }
}
