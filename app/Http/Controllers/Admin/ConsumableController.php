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

    /** Same name + brand + category = the same product, whatever the location. */
    public static function itemKey(?string $name, ?string $brand, ?string $category): string
    {
        return mb_strtolower(trim((string) $name)) . '|' . mb_strtolower(trim((string) $brand)) . '|' . mb_strtolower(trim((string) ($category ?: 'Consumable')));
    }

    /**
     * Put more units on an item that already exists (restock). No new product or number is created.
     * Manager/admin add the units straight away; anyone else sends them for approval.
     * Returns a short message for the screen.
     */
    private function addUnits(object $item, int $qty): string
    {
        $qty = max(1, $qty);
        if ($item->status === \App\Services\PartApprovalService::UNDER_REVIEW) {
            // Not live yet - the units simply join the item while it waits for approval.
            DB::table('parts_inventory')->where('id', $item->id)->update([
                'stock_qty' => (int) $item->stock_qty + $qty, 'updated_at' => now(),
            ]);
            return "Added {$qty} to {$item->part_code} (still waiting for manager approval).";
        }
        if (!\App\Services\PartApprovalService::canApprove()) {
            \App\Services\PartApprovalService::requestRestock((int) $item->id, $qty);
            return "{$item->part_code} already exists - {$qty} extra unit(s) sent for manager approval. Stock stays at {$item->stock_qty} until then.";
        }
        $new = (int) $item->stock_qty + $qty;
        DB::table('parts_inventory')->where('id', $item->id)->update([
            'stock_qty'  => $new,
            'status'     => in_array($item->status, ['Out of Stock', 'Sold'], true) ? 'Available' : $item->status,
            'updated_at' => now(),
        ]);
        return "Added {$qty} to {$item->part_code} - now {$new} in stock.";
    }

    /** Find the existing item for the same product at the same location. */
    private function findExisting(string $name, ?string $brand, string $category, string $location)
    {
        return DB::table('parts_inventory')->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(part_name)) = ?', [mb_strtolower(trim($name))])
            ->whereRaw("LOWER(TRIM(COALESCE(brand,''))) = ?", [mb_strtolower(trim((string) $brand))])
            ->where('part_category', $category)
            ->where('location', $location)
            ->orderBy('id')->first();
    }

    // =========================================================
    // POST /admin/consumables/{id}/add-stock  - quick restock from the list
    // =========================================================
    public function addStock(Request $request, int $id)
    {
        $request->validate(['qty' => 'required|integer|min:1|max:100000']);
        $item = DB::table('parts_inventory')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$item) abort(404);
        $msg = $this->addUnits($item, (int) $request->qty);
        return redirect()->back()->with('success', $msg);
    }

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

        // Up to 10 searches at once, separated by commas (an item shows if it matches any of them).
        $terms = collect(preg_split('/[,;\n|]+/', $q))->map(fn ($t) => trim($t))->filter()->unique()->take(10)->values();
        if ($terms->isNotEmpty()) {
            $query->where(function ($sq) use ($terms) {
                foreach ($terms as $t) {
                    $sq->orWhere(function ($one) use ($t) {
                        foreach (preg_split('/\s+/', $t) as $w) {
                            $one->where(function ($x) use ($w) {
                                $x->where('part_name', 'like', "%{$w}%")
                                  ->orWhere('part_code', 'like', "%{$w}%")
                                  ->orWhere('brand', 'like', "%{$w}%")
                                  ->orWhere('part_category', 'like', "%{$w}%");
                            });
                        }
                    });
                }
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

        // Stock of the same item (same name, brand, category) at every location, so one row can show
        // "Lagos 5 · Ibadan 4 · Ife 3 = 12 in total".
        $everywhere = [];
        $names = $consumables->pluck('part_name')->unique()->values()->all();
        if ($names) {
            $rows = DB::table('parts_inventory')->whereNull('deleted_at')
                ->whereIn('part_name', $names)->whereIn('part_category', self::CATEGORIES)
                ->where('status', 'Available')->where('stock_qty', '>', 0)
                ->get(['part_name', 'brand', 'part_category', 'location', 'stock_qty']);
            foreach ($rows as $r) {
                $k = self::itemKey($r->part_name, $r->brand, $r->part_category);
                $everywhere[$k]['locations'][$r->location] = ($everywhere[$k]['locations'][$r->location] ?? 0) + (int) $r->stock_qty;
                $everywhere[$k]['total'] = ($everywhere[$k]['total'] ?? 0) + (int) $r->stock_qty;
            }
        }

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
            'totalItems', 'totalValue', 'lowStock', 'everywhere'
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

        // Restock rule: the same product at the same location is never entered twice. The units are added to
        // the item that already exists, under its existing number.
        $existing = $this->findExisting($request->part_name, $request->brand ?? null, $category, $request->location);
        if ($existing) {
            return redirect()->route('admin.inventory.consumable.index')
                ->with('success', $this->addUnits($existing, (int) $request->stock_qty));
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

        $newConsumableId = DB::table('parts_inventory')->insertGetId([
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
            'status'           => \App\Services\PartApprovalService::initialStatus(),
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

        $waiting = \App\Services\PartApprovalService::submitNew($newConsumableId);

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', $waiting
                ? "\"{$request->part_name}\" ({$category}) saved and waiting for manager approval before it goes live."
                : "\"{$request->part_name}\" ({$category}) added to inventory.");
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

        // Approval rules: an item waiting for approval stays that way, and extra units added by staff below
        // manager are held as a restock request instead of going straight into stock.
        $newQty      = (int) $request->stock_qty;
        $newStatus   = null;
        $restockNote = '';
        if ($item) {
            $currentQty = (int) ($item->stock_qty ?? 0);
            if ($item->status === \App\Services\PartApprovalService::UNDER_REVIEW) {
                if (!\App\Services\PartApprovalService::canApprove()) {
                    $newStatus = \App\Services\PartApprovalService::UNDER_REVIEW;
                } else {
                    \App\Services\PartApprovalService::closeNewPart($id);
                }
            } elseif ($newQty > $currentQty && !\App\Services\PartApprovalService::canApprove()) {
                \App\Services\PartApprovalService::requestRestock($id, $newQty - $currentQty);
                $newQty = $currentQty;
                $restockNote = ' The extra units are waiting for manager approval; stock stays at ' . $currentQty . ' until then.';
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
            'stock_qty'       => $newQty,
            'status'          => $newStatus ?? ($newQty > 0 ? 'Available' : 'Out of Stock'),
            'description'     => $request->description ?? null,
            'photos'          => json_encode($existingPhotos),
            'updated_at'      => now(),
        ]);

        if ($item && $request->part_name !== $item->part_name && StaffRole::isSupervisorOrAbove()) {
            PartNames::ensureTerm($request->part_name, $request->part_category ?: ($item->part_category ?? 'Consumable'));
        }

        return redirect()->route('admin.inventory.consumable.index')
            ->with('success', 'Item updated successfully.' . $restockNote);
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
