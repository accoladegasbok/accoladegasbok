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
        DB::table('service_rate_prices')->where('service_rate_id', $id)->delete();
        return redirect()->route('admin.service-rates.index')->with('success', 'Service removed from catalog.');
    }

    // =========================================================
    // GET /admin/service-rates/{id}/prices — manage per-location prices
    // for this service. No FX conversion, ever — each location's price
    // is a real, fixed, admin-set number, same philosophy as parts.
    // =========================================================
    public function editPrices(int $id)
    {
        $service = DB::table('service_rates')->where('id', $id)->first();
        abort_if(!$service, 404);

        $prices = DB::table('service_rate_prices')->where('service_rate_id', $id)->get()->keyBy('location');
        $locations = ['Waxahachie TX','Kennedale TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'];

        return view('admin.service-rates.prices', compact('service', 'prices', 'locations'));
    }

    public function updatePrices(Request $request, int $id)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*' => 'required|numeric|min:0',
        ]);

        foreach ($request->prices as $location => $price) {
            $currencyCode = str_contains($location, 'Nigeria') ? 'NGN' : (str_contains($location, 'Ghana') ? 'GHS' : 'USD');
            DB::table('service_rate_prices')->updateOrInsert(
                ['service_rate_id' => $id, 'location' => $location],
                ['price_local' => $price, 'currency_code' => $currencyCode, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return redirect()->route('admin.service-rates.index')->with('success', 'Prices updated for all locations.');
    }

    // =========================================================
    // Static helper — the ONE place every other controller (Place
    // Order, Quick Receipt, Manual Invoice, Open Tab) should call to
    // get a service's correct price for a given location. Never
    // falls back to default_price silently without flagging it.
    // =========================================================
    public static function priceForLocation(int $serviceRateId, string $location): array
    {
        $row = DB::table('service_rate_prices')
            ->where('service_rate_id', $serviceRateId)
            ->where('location', $location)
            ->first();

        if ($row) {
            return ['price' => (float) $row->price_local, 'currency_code' => $row->currency_code, 'is_set' => true];
        }

        // No price set for this location yet — fall back to
        // default_price but flag it so the UI can warn staff, rather
        // than silently showing a wrong-currency number.
        $service = DB::table('service_rates')->where('id', $serviceRateId)->first();
        $currency = InvoiceController::currencyForLocation($location);
        return ['price' => (float) ($service->default_price ?? 0), 'currency_code' => $currency['code'], 'is_set' => false];
    }
}
