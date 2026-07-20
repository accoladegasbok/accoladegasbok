<?php
// FILE: app/Http/Controllers/Admin/ConsumableController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Manages consumable / generic parts — items bought in bulk and sold
 * individually that are NOT tied to a donor vehicle (e.g. oil filters,
 * brake pad sets, spark plug sets, engine oil, gaskets, belts etc.)
 *
 * Consumables live in parts_inventory with donor_vin = null and
 * part_category = 'Consumable' (or 'Generic') to distinguish them
 * from harvested parts.
 */
class ConsumableController extends Controller
{
    // =========================================================
    // GET /admin/consumables
    // =========================================================
    public function index(Request $request)
    {
        $q        = trim($request->get('q', ''));
        $location = $request->get('location', Session::get('staff_location', 'all'));
        $status   = $request->get('status', 'all');

        $query = DB::table('parts_inventory')
            ->whereNull('deleted_at')
            ->whereIn('part_category', ['Consumable', 'Generic', 'Generic / Consumable'])
            ->orWhereNull('donor_vin');

        // Re-scope after orWhereNull
        $query = DB::table('parts_inventory')
            ->whereNull('deleted_at')
            ->where(function ($q2) {
                $q2->whereIn('part_category', ['Consumable', 'Generic', 'Generic / Consumable'])
                   ->orWhereNull('donor_vin');
            });

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('part_name', 'like', "%{$q}%")
                   ->orWhere('part_code', 'like', "%{$q}%")
                   ->orWhere('brand', 'like', "%{$q}%");
            });
        }

        if ($location !== 'all') $query->where('location', $location);
        if ($status   !== 'all') $query->where('status', $status);

        $consumables = $query->orderBy('part_name')->paginate(30)->withQueryString();

        $locations = [
            'all', 'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria',
            'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
        ];

        // Stats
        $totalItems  = DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereIn('part_category', ['Consumable', 'Generic', 'Generic / Consumable'])->count();
        $totalValue  = DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereIn('part_category', ['Consumable', 'Generic', 'Generic / Consumable'])
            ->where('status', 'Available')->sum('price_local');
        $lowStock    = DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereIn('part_category', ['Consumable', 'Generic', 'Generic / Consumable'])
            ->where('status', 'Available')->where('stock_qty', '<=', 3)->count();

        return view('admin.consumables.index', compact(
            'consumables', 'q', 'location', 'status', 'locations',
            'totalItems', 'totalValue', 'lowStock'
        ));
    }

    // =========================================================
    // GET /admin/consumables/create
    // =========================================================
    public function create()
    {
        $partNames = \App\Data\PartNames::forCategory('Generic / Consumable');
        $locations = [
            'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria',
            'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
        ];

        $currency = InvoiceController::currencyForLocation(
            Session::get('staff_location', 'Waxahachie TX')
        );

        return view('admin.consumables.create', compact('partNames', 'locations', 'currency'));
    }

    // =========================================================
    // POST /admin/consumables
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'part_name'   => 'required|string|max:191',
            'location'    => 'required|string',
            'price_local' => 'required|numeric|min:0',
            'stock_qty'   => 'required|integer|min:1',
            // FIXED: photo upload never existed on this form at all —
            // 'photos' was hardcoded to an empty array on every save.
            'photos'      => 'nullable|array|max:6',
            'photos.*'    => 'nullable|image|max:5120', // 5MB per photo
        ]);

        $currency = InvoiceController::currencyForLocation($request->location);

        // Generate part code
        $lastCode = DB::table('parts_inventory')
            ->where('part_code', 'like', 'CON-%')
            ->orderByDesc('id')->value('part_code');
        $nextNum  = $lastCode ? (int) substr($lastCode, 4) + 1 : 1;
        $partCode = 'CON-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // Store uploaded photos (if any) — same disk/path convention
        // as the rest of the app (public disk, so they serve through
        // the /media symlink like every other part photo).
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if ($photo && $photo->isValid()) {
                    $photoPaths[] = $photo->store('parts-photos', 'public');
                }
            }
        }

        DB::table('parts_inventory')->insert([
            'part_code'        => $partCode,
            'part_name'        => $request->part_name,
            'part_category'    => 'Consumable',
            'brand'            => $request->brand ?? null,
            'model'            => null,
            'year_from'        => null,
            'year_to'          => null,
            'compat_year_from' => null,
            'compat_year_to'   => null,
            'donor_vin'        => null,
            'location'         => $request->location,
            'price_local'      => (float) $request->price_local,
            'price_usd'        => (float) $request->price_local,
            'price_wholesale'  => $request->price_wholesale ? (float) $request->price_wholesale : null,
            'currency_code'    => $currency['code'],
            'stock_qty'        => (int) $request->stock_qty,
            'condition_grade'  => 'New',
            'status'           => 'Available',
            'origin'           => 'N/A',
            'origin_market'    => 'N/A',
            'description'      => $request->description ?? null,
            'photos'           => json_encode($photoPaths),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', "Consumable \"{$request->part_name}\" added to inventory.");
    }

    // =========================================================
    // GET /admin/consumables/{id}/edit
    // =========================================================
    public function edit(int $id)
    {
        $item = DB::table('parts_inventory')->where('id', $id)->first();
        if (!$item) abort(404);

        $locations = [
            'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria',
            'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
        ];

        $currency = InvoiceController::currencyForLocation($item->location ?? 'Waxahachie TX');

        return view('admin.consumables.edit', compact('item', 'locations', 'currency'));
    }

    // =========================================================
    // PUT /admin/consumables/{id}
    // =========================================================
    public function update(Request $request, int $id)
    {
        $request->validate([
            'part_name'   => 'required|string|max:191',
            'price_local' => 'required|numeric|min:0',
            'stock_qty'   => 'required|integer|min:0',
            // FIXED: same gap as store() — edit form could never
            // actually save a photo, regardless of what was uploaded.
            'photos'         => 'nullable|array|max:6',
            'photos.*'       => 'nullable|image|max:5120',
            'remove_photos'  => 'nullable|array', // indices of existing photos staff want removed
        ]);

        $item = DB::table('parts_inventory')->where('id', $id)->first();
        $existingPhotos = json_decode($item->photos ?? '[]', true) ?: [];

        // Remove any photos staff flagged for deletion
        if (!empty($request->remove_photos)) {
            foreach ($request->remove_photos as $idx) {
                if (isset($existingPhotos[$idx])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingPhotos[$idx]);
                    unset($existingPhotos[$idx]);
                }
            }
            $existingPhotos = array_values($existingPhotos);
        }

        // Append newly uploaded photos to whatever's left
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if ($photo && $photo->isValid()) {
                    $existingPhotos[] = $photo->store('parts-photos', 'public');
                }
            }
        }

        DB::table('parts_inventory')->where('id', $id)->update([
            'part_name'       => $request->part_name,
            'brand'           => $request->brand ?? null,
            'price_local'     => (float) $request->price_local,
            'price_usd'       => (float) $request->price_local,
            'price_wholesale' => $request->price_wholesale ? (float) $request->price_wholesale : null,
            'stock_qty'       => (int) $request->stock_qty,
            'status'          => $request->stock_qty > 0 ? 'Available' : 'Out of Stock',
            'description'     => $request->description ?? null,
            'photos'          => json_encode($existingPhotos),
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', 'Consumable updated successfully.');
    }

    // =========================================================
    // DELETE /admin/consumables/{id}
    // =========================================================
    public function destroy(int $id)
    {
        DB::table('parts_inventory')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', 'Consumable removed.');
    }
}
