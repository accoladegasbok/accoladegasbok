<?php
// FILE: app/Http/Controllers/Admin/FitmentController.php
//
// Staff fitment tools:
//   - Pin records: donor VIN + connector photo + terminal count. Any staff member can submit one;
//     only a manager or admin can verify or reject it. A verified record replaces the built-in guess.
//   - VIN decode helper that pre-fills year / make / model / engine / gearbox. Pre-filled values are
//     suggestions for staff to confirm, never saved as verified.
//   - Review queue for staged fitment lists (nothing there is public).

namespace App\Http\Controllers\Admin;

use App\Data\FitmentStatus;
use App\Data\OemDatabase;
use App\Http\Controllers\Controller;
use App\Services\PartApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class FitmentController extends Controller
{
    private function canVerify(): bool
    {
        return PartApprovalService::canApprove(); // manager or admin
    }

    // GET /admin/fitment?tab=pins|staging&status=...
    public function index(Request $request)
    {
        $tab = $request->get('tab') === 'staging' ? 'staging' : 'pins';

        $pinsReady     = Schema::hasTable('pin_records');
        $stagingReady  = Schema::hasTable('fitment_staging');

        $pinStatus = in_array($request->get('status'), ['pending', 'verified', 'rejected'], true) ? $request->get('status') : 'pending';
        $pins = collect();
        $pinCounts = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
        if ($pinsReady) {
            $pins = DB::table('pin_records')->where('status', $pinStatus)->orderByDesc('created_at')->paginate(30, ['*'], 'pp')->withQueryString();
            foreach (DB::table('pin_records')->select('status', DB::raw('count(*) n'))->groupBy('status')->pluck('n', 'status') as $k => $v) $pinCounts[$k] = (int) $v;
        }

        $stageStatus = in_array($request->get('sstatus'), ['staged', 'approved', 'rejected'], true) ? $request->get('sstatus') : 'staged';
        $staged = collect();
        $stageCounts = ['staged' => 0, 'approved' => 0, 'rejected' => 0];
        if ($stagingReady) {
            $sq = DB::table('fitment_staging')->where('status', $stageStatus);
            if ($mk = trim((string) $request->get('make', ''))) $sq->where('make', $mk);
            $staged = $sq->orderBy('make')->orderBy('model')->orderBy('donor_years')->paginate(40, ['*'], 'sp')->withQueryString();
            foreach (DB::table('fitment_staging')->select('status', DB::raw('count(*) n'))->groupBy('status')->pluck('n', 'status') as $k => $v) $stageCounts[$k] = (int) $v;
        }

        return view('admin.fitment.index', [
            'tab' => $tab, 'pins' => $pins, 'pinStatus' => $pinStatus, 'pinCounts' => $pinCounts, 'pinsReady' => $pinsReady,
            'staged' => $staged, 'stageStatus' => $stageStatus, 'stageCounts' => $stageCounts, 'stagingReady' => $stagingReady,
            'canVerify' => $this->canVerify(),
            'statusLabels' => FitmentStatus::all(),
        ]);
    }

    // GET /admin/fitment/decode-vin?vin=...   (JSON)
    public function decodeVin(Request $request)
    {
        $vin = strtoupper(trim((string) $request->get('vin')));
        if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            return response()->json(['error' => 'A VIN is 17 letters and numbers (no I, O or Q).'], 422);
        }

        try {
            $res = Http::timeout(8)->get("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json");
            if ($res->failed()) return response()->json(['error' => 'The VIN service did not answer. Fill the fields by hand.'], 502);
            $r = collect($res->json('Results', []));
            $v = fn (string $name) => ($r->firstWhere('Variable', $name)['Value'] ?? null);

            $make = $v('Make'); $model = $v('Model'); $year = (int) $v('Model Year');
            if (!$make || !$model || !$year) return response()->json(['error' => 'Could not decode this VIN. Fill the fields by hand.'], 422);

            $cyl = (int) $v('Engine Number of Cylinders');
            $litres = (float) $v('Displacement (L)');
            $oem = OemDatabase::lookup($make, $model, $year, $cyl, $litres);

            $existing = Schema::hasTable('pin_records')
                ? DB::table('pin_records')->where('donor_vin', $vin)->orderByDesc('id')->first(['id', 'status', 'pin_count', 'transmission_code'])
                : null;

            return response()->json([
                'make' => ucwords(strtolower($make)), 'model' => $model, 'year' => $year,
                'engine_l' => $litres ?: null, 'cylinders' => $cyl ?: null, 'drive_type' => $v('Drive Type'),
                // suggestions only: staff confirm against the real unit
                'suggest_engine_code'       => $oem['engine_code'] ?? null,
                'suggest_transmission_code' => $oem['transmission_code'] ?? null,
                'suggest_pin_count'         => $oem['pin_count'] ?? null,
                'pin_verified'              => (bool) ($oem['pin_verified'] ?? false),
                'multiple_engines'          => (bool) ($oem['multiple_engines'] ?? false),
                'note'                      => $oem['market_note'] ?? null,
                'existing_record'           => $existing,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'The VIN service is not reachable. Fill the fields by hand.'], 502);
        }
    }

    // POST /admin/fitment/pins
    public function storePin(Request $request)
    {
        $data = $request->validate([
            'donor_vin'         => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i'],
            'make'              => 'required|string|max:60',
            'model'             => 'required|string|max:80',
            'year_from'         => 'required|integer|min:1980|max:2035',
            'year_to'           => 'required|integer|min:1980|max:2035|gte:year_from',
            'engine_code'       => 'nullable|string|max:20',
            'transmission_code' => 'nullable|string|max:20',
            'pin_count'         => 'required|integer|min:1|max:40',
            'photo'             => 'required|image|max:8192',
            'part_code'         => 'nullable|string|max:40',
            'notes'             => 'nullable|string|max:1000',
        ], [
            'donor_vin.regex' => 'The VIN must be 17 letters and numbers (no I, O or Q).',
            'photo.required'  => 'Add a photo of the connector. A pin count cannot be saved without it.',
        ]);

        $partId = null;
        if (!empty($data['part_code'])) {
            $partId = DB::table('parts_inventory')->where('part_code', trim($data['part_code']))->value('id');
            if (!$partId) return back()->withInput()->withErrors(['part_code' => 'No stock item has that number.']);
        }

        $path = $request->file('photo')->store('pin-photos', 'public');
        $verifyNow = $this->canVerify() && $request->boolean('verify_now');
        $name = Session::get('staff_name') ?? 'Staff';

        DB::table('pin_records')->insert([
            'make'  => ucwords(strtolower(trim($data['make']))),
            'model' => trim($data['model']),
            'year_from' => (int) $data['year_from'],
            'year_to'   => (int) $data['year_to'],
            'engine_code'       => ($c = strtoupper(trim((string) ($data['engine_code'] ?? '')))) !== '' ? $c : null,
            'transmission_code' => ($c = strtoupper(trim((string) ($data['transmission_code'] ?? '')))) !== '' ? $c : null,
            'pin_count' => (int) $data['pin_count'],
            'donor_vin' => strtoupper($data['donor_vin']),
            'photo_path' => $path,
            'part_id'   => $partId,
            'notes'     => $data['notes'] ?? null,
            'status'    => $verifyNow ? 'verified' : 'pending',
            'submitted_by_staff_id' => Session::get('staff_id'),
            'submitted_by_name'     => $name,
            'verified_by_staff_id'  => $verifyNow ? Session::get('staff_id') : null,
            'verified_by_name'      => $verifyNow ? $name : null,
            'verified_at'           => $verifyNow ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return redirect()->route('admin.fitment.index', ['tab' => 'pins'])
            ->with('success', $verifyNow ? 'Pin count saved and verified.' : 'Pin count saved. A manager or admin will verify it.');
    }

    // POST /admin/fitment/pins/{id}/verify
    public function verifyPin(int $id)
    {
        abort_unless($this->canVerify(), 403, 'Only a manager or admin can verify a pin count.');
        $rec = DB::table('pin_records')->where('id', $id)->first();
        abort_if(!$rec, 404);

        DB::table('pin_records')->where('id', $id)->update([
            'status' => 'verified', 'reject_reason' => null,
            'verified_by_staff_id' => Session::get('staff_id'),
            'verified_by_name' => Session::get('staff_name') ?? 'Staff',
            'verified_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Pin count verified.');
    }

    // POST /admin/fitment/pins/{id}/reject
    public function rejectPin(Request $request, int $id)
    {
        abort_unless($this->canVerify(), 403, 'Only a manager or admin can reject a pin count.');
        $request->validate(['reason' => 'required|string|max:255']);
        DB::table('pin_records')->where('id', $id)->update([
            'status' => 'rejected', 'reject_reason' => $request->reason,
            'verified_by_staff_id' => Session::get('staff_id'),
            'verified_by_name' => Session::get('staff_name') ?? 'Staff',
            'verified_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Pin record rejected.');
    }

    // DELETE /admin/fitment/pins/{id}  (admin only; removes the record and its photo)
    public function deletePin(int $id)
    {
        abort_unless(Session::get('staff_role') === 'admin', 403, 'Only an admin can delete a pin record.');
        $rec = DB::table('pin_records')->where('id', $id)->first();
        abort_if(!$rec, 404);
        Storage::disk('public')->delete($rec->photo_path);
        DB::table('pin_records')->where('id', $id)->delete();
        return back()->with('success', 'Pin record deleted.');
    }

    // POST /admin/fitment/staging/{id}/{decision}   approved | rejected | staged
    public function stagingDecision(int $id, string $decision)
    {
        abort_unless($this->canVerify(), 403, 'Only a manager or admin can review staged fitment.');
        abort_unless(in_array($decision, ['approved', 'rejected', 'staged'], true), 404);
        DB::table('fitment_staging')->where('id', $id)->update(['status' => $decision, 'updated_at' => now()]);
        return back()->with('success', 'Updated.');
    }
}
