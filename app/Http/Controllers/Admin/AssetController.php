<?php
// FILE: app/Http/Controllers/Admin/AssetController.php
//
// Office equipment / machinery / tools register. This is intentionally
// a completely separate table from parts_inventory — assets here are
// NEVER for sale, never appear in customer search, the POS scanner,
// or any customer-facing page. This exists purely for internal
// stock-taking of things the business owns and uses (printers,
// generators, forklifts, computers, etc.), tracking condition and
// service history over time.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AssetController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
    ];

    const CATEGORIES = ['Office Equipment', 'Machinery', 'Vehicle', 'Tool', 'IT Equipment', 'Furniture', 'Other'];

    const STATUSES = ['In Service', 'Serviceable', 'Needs Repair', 'Out of Service', 'Retired'];

    public function index(Request $request)
    {
        $query = DB::table('assets')->orderByDesc('created_at');

        if ($cat = $request->get('category'))     $query->where('category', $cat);
        if ($status = $request->get('status'))    $query->where('status', $status);
        if ($loc = $request->get('location'))      $query->where('location', $loc);
        if ($q = $request->get('q')) {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('asset_tag', 'like', "%{$q}%")
                   ->orWhere('serial_number', 'like', "%{$q}%");
            });
        }

        $assets = $query->paginate(30)->withQueryString();

        $counts = [
            'total'         => DB::table('assets')->count(),
            'needs_repair'  => DB::table('assets')->where('status', 'Needs Repair')->count(),
            'out_of_service'=> DB::table('assets')->where('status', 'Out of Service')->count(),
        ];

        return view('admin.assets.index', [
            'assets' => $assets, 'counts' => $counts,
            'locations' => self::LOCATIONS, 'categories' => self::CATEGORIES, 'statuses' => self::STATUSES,
        ]);
    }

    public function create()
    {
        return view('admin.assets.create', [
            'locations' => self::LOCATIONS, 'categories' => self::CATEGORIES, 'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:150',
            'category'           => 'required|string',
            'location'           => 'required|string',
            'status'             => 'required|string',
            'serial_number'      => 'nullable|string|max:100',
            'assigned_to'        => 'nullable|string|max:100',
            'acquired_date'      => 'nullable|date',
            'acquired_value'     => 'nullable|numeric|min:0',
            'acquired_currency'  => 'nullable|string|max:5',
            'last_serviced_date' => 'nullable|date',
            'next_service_due'   => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
        ]);

        $year = date('Y');
        $seq  = DB::table('assets')->whereYear('created_at', $year)->count() + 1;
        $assetTag = "AST-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $id = DB::table('assets')->insertGetId([
            'asset_tag'           => $assetTag,
            'name'                => $request->name,
            'category'            => $request->category,
            'location'            => $request->location,
            'status'              => $request->status,
            'serial_number'       => $request->serial_number,
            'assigned_to'         => $request->assigned_to,
            'acquired_date'       => $request->acquired_date,
            'acquired_value'      => $request->acquired_value,
            'acquired_currency'   => $request->acquired_currency,
            'last_serviced_date'  => $request->last_serviced_date,
            'next_service_due'    => $request->next_service_due,
            'notes'               => $request->notes,
            'created_by_staff_id' => Session::get('staff_id'),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('admin.assets.show', $id)->with('success', "{$assetTag} added.");
    }

    public function show(int $id)
    {
        $asset = DB::table('assets')->where('id', $id)->first();
        abort_if(!$asset, 404);

        $logs = DB::table('asset_logs')->where('asset_id', $id)->orderByDesc('created_at')->get();

        return view('admin.assets.show', compact('asset', 'logs'));
    }

    public function edit(int $id)
    {
        $asset = DB::table('assets')->where('id', $id)->first();
        abort_if(!$asset, 404);

        return view('admin.assets.edit', [
            'asset' => $asset, 'locations' => self::LOCATIONS, 'categories' => self::CATEGORIES, 'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $asset = DB::table('assets')->where('id', $id)->first();
        abort_if(!$asset, 404);

        $request->validate([
            'name'               => 'required|string|max:150',
            'category'           => 'required|string',
            'location'           => 'required|string',
            'status'             => 'required|string',
            'serial_number'      => 'nullable|string|max:100',
            'assigned_to'        => 'nullable|string|max:100',
            'acquired_date'      => 'nullable|date',
            'acquired_value'     => 'nullable|numeric|min:0',
            'acquired_currency'  => 'nullable|string|max:5',
            'last_serviced_date' => 'nullable|date',
            'next_service_due'   => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
        ]);

        // Log meaningful changes for the asset's history trail
        if ($asset->status !== $request->status) {
            DB::table('asset_logs')->insert([
                'asset_id' => $id, 'action' => 'status_change',
                'from_value' => $asset->status, 'to_value' => $request->status,
                'staff_id' => Session::get('staff_id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if ($asset->location !== $request->location) {
            DB::table('asset_logs')->insert([
                'asset_id' => $id, 'action' => 'location_change',
                'from_value' => $asset->location, 'to_value' => $request->location,
                'staff_id' => Session::get('staff_id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if ($request->last_serviced_date && $request->last_serviced_date !== $asset->last_serviced_date) {
            DB::table('asset_logs')->insert([
                'asset_id' => $id, 'action' => 'serviced',
                'to_value' => $request->last_serviced_date,
                'staff_id' => Session::get('staff_id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('assets')->where('id', $id)->update([
            'name'               => $request->name,
            'category'           => $request->category,
            'location'           => $request->location,
            'status'             => $request->status,
            'serial_number'      => $request->serial_number,
            'assigned_to'        => $request->assigned_to,
            'acquired_date'      => $request->acquired_date,
            'acquired_value'     => $request->acquired_value,
            'acquired_currency'  => $request->acquired_currency,
            'last_serviced_date' => $request->last_serviced_date,
            'next_service_due'   => $request->next_service_due,
            'notes'              => $request->notes,
            'updated_at'         => now(),
        ]);

        return redirect()->route('admin.assets.show', $id)->with('success', 'Asset updated.');
    }

    public function destroy(int $id)
    {
        DB::table('asset_logs')->where('asset_id', $id)->delete();
        DB::table('assets')->where('id', $id)->delete();
        return redirect()->route('admin.assets.index')->with('success', 'Asset removed from register.');
    }

    // GET /admin/assets/{id}/barcode — printable barcode label
    public function barcode(int $id)
    {
        $asset = DB::table('assets')->where('id', $id)->first();
        abort_if(!$asset, 404);
        return view('admin.assets.barcode', compact('asset'));
    }
}
