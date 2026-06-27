<?php
// FILE: app/Http/Controllers/Admin/ServiceRateController.php
// Admin-managed catalog of fixed-rate services (labor/misc charges that
// never touch parts inventory) — e.g. "Brake Pad Replacement (Labor)".

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRateController extends Controller
{
    public function index()
    {
        $rates = DB::table('service_rates')->orderBy('category')->orderBy('name')->get();
        return view('admin.service-rates.index', compact('rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'category'      => 'nullable|string|max:60',
            'default_price' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:500',
        ]);

        $seq = DB::table('service_rates')->count() + 1;
        $serviceCode = 'SVC-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        DB::table('service_rates')->insert([
            'service_code'  => $serviceCode,
            'name'          => $request->name,
            'category'      => $request->category,
            'default_price' => $request->default_price,
            'notes'         => $request->notes,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.service-rates.index')->with('success', "\"{$request->name}\" added to the service catalog.");
    }

    // GET /admin/service-rates/{id}/barcode — printable barcode label
    public function barcode(int $id)
    {
        $service = DB::table('service_rates')->where('id', $id)->first();
        abort_if(!$service, 404);
        return view('admin.service-rates.barcode', compact('service'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'          => 'required|string|max:150',
            'category'      => 'nullable|string|max:60',
            'default_price' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:500',
            'is_active'     => 'nullable|boolean',
        ]);

        DB::table('service_rates')->where('id', $id)->update([
            'name'          => $request->name,
            'category'      => $request->category,
            'default_price' => $request->default_price,
            'notes'         => $request->notes,
            'is_active'     => $request->boolean('is_active'),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.service-rates.index')->with('success', 'Service updated.');
    }

    public function destroy(int $id)
    {
        DB::table('service_rates')->where('id', $id)->delete();
        return redirect()->route('admin.service-rates.index')->with('success', 'Service removed from catalog.');
    }
}
