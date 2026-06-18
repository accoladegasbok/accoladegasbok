<?php
// FILE: app/Http/Controllers/Admin/InventoryController.php
// Updated: pin_count, gear_alias, engine_code_oem, transmission_code_oem,
//          origin_market, fitment_notes, compat years, compatible_trims

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InventoryController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX','Elkhorn WI',
        'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana',
    ];

    const BRANDS = [
        'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz',
        'Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda',
    ];

    const CATEGORIES = [
        'Engine','Transmission','Body','Suspension','Electrical',
        'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels',
    ];

    // Year range 1986–2027 (item 5)
    private function yearRange(): array
    {
        return range(1986, 2027);
    }

    // ── List ──────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DB::table('parts_inventory')->orderByDesc('created_at');

        if ($f = $request->get('brand'))    $query->where('brand', $f);
        if ($f = $request->get('category')) $query->where('part_category', $f);
        if ($f = $request->get('location')) $query->where('location', $f);
        if ($f = $request->get('status'))   $query->where('status', $f);
        if ($f = $request->get('q')) {
            $query->where(function($q) use ($f) {
                $q->where('part_name',           'like', "%$f%")
                  ->orWhere('part_code',          'like', "%$f%")
                  ->orWhere('model',              'like', "%$f%")
                  ->orWhere('oem_part_number',    'like', "%$f%")
                  ->orWhere('engine_code_oem',    'like', "%$f%")  // item 6
                  ->orWhere('transmission_code_oem','like',"%$f%") // item 6
                  ->orWhere('gear_alias',         'like', "%$f%")  // item 7
                  ->orWhere('pin_count',          'like', "%$f%"); // item 10
            });
        }

        $parts  = $query->paginate(30)->withQueryString();
        $counts = DB::table('parts_inventory')
            ->select('status', DB::raw('count(*) as n'))
            ->groupBy('status')->pluck('n','status');

        return view('admin.inventory.index', [
            'parts'      => $parts,
            'counts'     => $counts,
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => self::LOCATIONS,
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────
    public function edit(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        if (!$part) abort(404);

        return view('admin.inventory.edit', [
            'part'      => $part,
            'brands'    => self::BRANDS,
            'categories'=> self::CATEGORIES,
            'locations' => self::LOCATIONS,
            'years'     => $this->yearRange(),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $request->validate([
            'part_name'           => 'required|string|max:150',
            'price_usd'           => 'required|numeric|min:0',
            'condition_grade'     => 'required|in:A,B,C,New',
            'status'              => 'required|in:Available,Reserved,Sold',
            'location'            => 'required|string',
            'description'         => 'nullable|string|max:1000',
            'oem_part_number'     => 'nullable|string|max:80',
            'mileage'             => 'nullable|integer|min:0',
            'colour'              => 'nullable|string|max:50',
            'bin_location'        => 'nullable|string|max:20',
            'engine_code_oem'     => 'nullable|string|max:30',
            'transmission_code_oem'=> 'nullable|string|max:30',
            'pin_count'           => 'nullable|integer|min:1|max:99',
            'gear_alias'          => 'nullable|string|max:50',
            'origin_market'       => 'nullable|in:JDM,USDM,EDM,Nigerian Used,N/A',
            'fitment_notes'       => 'nullable|string',
            'compat_year_from'    => 'nullable|integer|min:1986|max:2027',
            'compat_year_to'      => 'nullable|integer|min:1986|max:2027',
            'compatible_trims'    => 'nullable|string|max:200',
            'not_compatible_note' => 'nullable|string|max:200',
        ]);

        DB::table('parts_inventory')->where('id', $id)->update([
            'part_name'              => $request->part_name,
            'price_usd'              => $request->price_usd,
            'condition_grade'        => $request->condition_grade,
            'status'                 => $request->status,
            'location'               => $request->location,
            'description'            => $request->description,
            'oem_part_number'        => $request->oem_part_number,
            'mileage'                => $request->mileage,
            'colour'                 => $request->colour,
            'bin_location'           => $request->bin_location,
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
            'origin_market'          => $request->origin_market ?? 'N/A',
            'fitment_notes'          => $request->fitment_notes,
            'compat_year_from'       => $request->compat_year_from,
            'compat_year_to'         => $request->compat_year_to,
            'compatible_trims'       => $request->compatible_trims,
            'not_compatible_note'    => $request->not_compatible_note,
            'updated_at'             => now(),
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Part updated successfully.');
    }

    // ── Quick status update (AJAX) ────────────────────────────────
    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:Available,Reserved,Sold']);
        DB::table('parts_inventory')->where('id', $id)->update([
            'status' => $request->status, 'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin','manager'])) abort(403);
        DB::table('parts_inventory')->where('id', $id)->delete();
        return redirect()->route('admin.inventory.index')->with('success', 'Part deleted.');
    }

    // ── Create form ───────────────────────────────────────────────
    public function create()
    {
        return view('admin.inventory.create', [
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => self::LOCATIONS,
            'years'      => $this->yearRange(),
        ]);
    }

    // ── Store manually entered part ───────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'brand'          => 'required|string',
            'model'          => 'required|string|max:80',
            'year_from'      => 'required|integer|min:1986|max:2027',
            'year_to'        => 'required|integer|min:1986|max:2027',
            'part_name'      => 'required|string|max:150',
            'part_category'  => 'required|string',
            'price_usd'      => 'required|numeric|min:0',
            'condition_grade'=> 'required|in:A,B,C,New',
            'location'       => 'required|string',
        ]);

        $prefix   = substr(strtoupper($request->part_category), 0, 3);
        $lastCode = DB::table('parts_inventory')
            ->where('part_code','like',$prefix.'-%')
            ->orderByDesc('id')->value('part_code');
        $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix)+1) + 1 : 1;
        $partCode = $prefix.'-'.str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        DB::table('parts_inventory')->insert([
            'part_code'              => $partCode,
            'brand'                  => $request->brand,
            'model'                  => $request->model,
            'year_from'              => $request->year_from,
            'year_to'                => $request->year_to,
            'compat_year_from'       => $request->compat_year_from ?? $request->year_from,
            'compat_year_to'         => $request->compat_year_to   ?? $request->year_to,
            'part_name'              => $request->part_name,
            'part_category'          => $request->part_category,
            'side'                   => $request->side ?? 'N/A',
            'condition_grade'        => $request->condition_grade,
            'price_usd'              => $request->price_usd,
            'location'               => $request->location,
            'oem_part_number'        => $request->oem_part_number,
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
            'origin_market'          => $request->origin_market ?? 'N/A',
            'fitment_notes'          => $request->fitment_notes,
            'compatible_trims'       => $request->compatible_trims,
            'not_compatible_note'    => $request->not_compatible_note,
            'mileage'                => $request->mileage,
            'colour'                 => $request->colour,
            'description'            => $request->description,
            'stock_qty'              => 1,
            'status'                 => 'Available',
            'photos'                 => '[]',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', "Part {$partCode} added to inventory.");
    }
}
