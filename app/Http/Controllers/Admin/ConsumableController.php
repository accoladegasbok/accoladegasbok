<?php
// FILE: app/Http/Controllers/Admin/ConsumableController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Data\PartNames;
use App\Services\PartCodeService;
use App\Support\StaffRole;

/**
 * Manages non-automotive inventory: Consumables (oils, filters, brake
 * pad sets, gaskets etc.), Electronics, Computers, and Other — items
 * bought in bulk/individually that are NOT tied to a donor vehicle.
 *
 * FIXED: this previously only ever created 'Consumable' category
 * items — store() hardcoded 'part_category' => 'Consumable'
 * unconditionally, so Electronics/Computers/Other had no confirmed
 * creation path anywhere in the app. Now accepts a category selection
 * (defaulting to Consumable for backward compatibility with any
 * existing bookmarked/linked create form), validated against the same
 * four categories the public /other-items page groups together.
 *
 * These live in parts_inventory with donor_vin = null, distinguishing
 * them from harvested parts.
 */
class ConsumableController extends Controller
{
    const CATEGORIES = ['Consumable', 'Electronics', 'Computers', 'Other'];

    // =========================================================
    // GET /admin/consumables
    // =========================================================
    public function index(Request $request)
    {
        $q        = trim($request->get('q', ''));
        $location = $request->get('location', Session::get('staff_location', 'all'));
        $status   = $request->get('status', 'all');
        // NEW: filter the admin list by category too, now that this
        // screen covers four categories instead of just one.
        $category = $request->get('category', 'all');

        $query = DB::table('parts_inventory')
            ->whereNull('deleted_at')
            ->where(function ($q2) {
                $q2->whereIn('part_category', self::CATEGORIES)
                   ->orWhereNull('donor_vin');
            });

        if ($category !== 'all' && in_array($category, self::CATEGORIES, true)) {
            $query->where('part_category', $category);
        }

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
            ->whereIn('part_category', self::CATEGORIES)->count();
        $totalValue  = DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereIn('part_category', self::CATEGORIES)
            ->where('status', 'Available')->sum('price_local');
        $lowStock    = DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereIn('part_category', self::CATEGORIES)
            ->where('status', 'Available')->where('stock_qty', '<=', 3)->count();

        return view('admin.consumables.index', compact(
            'consumables', 'q', 'location', 'status', 'category', 'locations',
            'totalItems', 'totalValue', 'lowStock'
        ))->with('categories', self::CATEGORIES);
    }

    // =========================================================
    // GET /admin/consumables/create
    // =========================================================
    public function create()
    {
        // Built-in consumable names PLUS anything added through the Part Names
        // Manager (or typed by a supervisor) for consumables/electronics/etc.
        $partNames = PartNames::consumableNames();
        $locations = [
            'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria',
            'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
        ];

        $currency = InvoiceController::currencyForLocation(
            Session::get('staff_location', 'Waxahachie TX')
        );

        // NEW: category selector — was entirely absent before, since
        // this form only ever created Consumable items.
        return view('admin.consumables.create', compact('partNames', 'locations', 'currency'))
            ->with('categories', self::CATEGORIES);
    }

    // =========================================================
    // POST /admin/consumables
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'part_name'   => 'required|string|max:191',
            // NEW: was hardcoded to 'Consumable' — now a real,
            // validated selection across all four grouped categories.
            'part_category' => 'nullable|string|in:' . implode(',', self::CATEGORIES),
            'location'    => 'required|string',
            'price_local' => 'required|numeric|min:0',
            'stock_qty'   => 'required|integer|min:1',
            'photos'      => 'nullable|array|max:6',
            'photos.*'    => 'nullable|image|max:5120', // 5MB per photo
        ]);

        $category = $request->part_category ?: 'Consumable';
        $currency = InvoiceController::currencyForLocation($request->location);

        // Part name guard — same rule as every other entry point. Before
        // this, any text was accepted here, so consumables could drift away
        // from the shared list. Supervisor and above may add new names;
        // everyone else picks from the list.
        if (!StaffRole::isSupervisorOrAbove() && !in_array($request->part_name, PartNames::flat(), true)) {
            return back()->withInput()->withErrors([
                'part_name' => 'Pick a name from the list, or ask a supervisor to add a new one.',
            ]);
        }

        // Generate part code — kept as a single CON- sequence across
        // all four categories (they're grouped together on the public
        // side too, so one shared numbering scheme is simplest).
        $partCode = PartCodeService::next('CON');

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
            'part_category'    => $category,
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

        // A supervisor-typed new name joins the shared list.
        if (StaffRole::isSupervisorOrAbove()) {
            PartNames::ensureTerm($request->part_name, $category);
        }

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', "\"{$request->part_name}\" ({$category}) added to inventory.");
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

        return view('admin.consumables.edit', compact('item', 'locations', 'currency'))
            ->with('categories', self::CATEGORIES)
            ->with('partNames', PartNames::consumableNames());
    }

    // =========================================================
    // PUT /admin/consumables/{id}
    // =========================================================
    public function update(Request $request, int $id)
    {
        $request->validate([
            'part_name'   => 'required|string|max:191',
            // NEW: category can now actually be corrected on edit too
            // (e.g. something entered as Consumable that's really an
            // Electronics item).
            'part_category' => 'nullable|string|in:' . implode(',', self::CATEGORIES),
            'price_local' => 'required|numeric|min:0',
            'stock_qty'   => 'required|integer|min:0',
            'photos'         => 'nullable|array|max:6',
            'photos.*'       => 'nullable|image|max:5120',
            'remove_photos'  => 'nullable|array',
        ]);

        $item = DB::table('parts_inventory')->where('id', $id)->first();

        // Only check the name when it was actually changed, so editing price
        // or stock on an older item with a custom name is never blocked.
        if ($item && $request->part_name !== $item->part_name
            && !StaffRole::isSupervisorOrAbove()
            && !in_array($request->part_name, PartNames::flat(), true)) {
            return back()->withInput()->withErrors([
                'part_name' => 'Pick a name from the list, or ask a supervisor to add a new one.',
            ]);
        }

        $existingPhotos = json_decode($item->photos ?? '[]', true) ?: [];

        if (!empty($request->remove_photos)) {
            foreach ($request->remove_photos as $idx) {
                if (isset($existingPhotos[$idx])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingPhotos[$idx]);
                    unset($existingPhotos[$idx]);
                }
            }
            $existingPhotos = array_values($existingPhotos);
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if ($photo && $photo->isValid()) {
                    $existingPhotos[] = $photo->store('parts-photos', 'public');
                }
            }
        }

        DB::table('parts_inventory')->where('id', $id)->update([
            'part_name'       => $request->part_name,
            // Only updates if actually submitted — safe no-op until
            // the edit form itself adds the category selector.
            ...($request->filled('part_category') ? ['part_category' => $request->part_category] : []),
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

        if ($item && $request->part_name !== $item->part_name && StaffRole::isSupervisorOrAbove()) {
            PartNames::ensureTerm($request->part_name, $request->part_category ?: ($item->part_category ?? 'Consumable'));
        }

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', 'Item updated successfully.');
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
            ->with('success', 'Item removed.');
    }
}
