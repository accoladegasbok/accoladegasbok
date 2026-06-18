<?php
// FILE: app/Http/Controllers/Admin/HarvestController.php
// Updated saveParts() — now stores compat_year_from/to, OEM codes, gear alias defaults

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class HarvestController extends Controller
{
    // Known OEM engine+transmission codes per model (for auto-fill)
    const OEM_CODES = [
        // Toyota Corolla
        'Corolla' => [
            [1986,2008,'3ZZ-FE',  null],
            [2003,2008,'2ZZ-GE',  null],
            [2009,2019,'2ZR-FE',  'U341E'],
            [2020,2027,'M20A-FKS',null],
        ],
        // Toyota Camry
        'Camry' => [
            [1992,2001,'5S-FE',   'A140E'],
            [2002,2006,'2AZ-FE',  'U241E'],
            [2007,2011,'2AZ-FE',  'U760E'],
            [2007,2011,'2GR-FE',  'U660E'],
            [2012,2017,'2AR-FE',  'U760E'],
            [2012,2017,'2GR-FE',  'A750E'],
            [2018,2024,'A25A-FXS',null],
        ],
        // Toyota Corolla Cross
        'Corolla Cross' => [
            [2022,2027,'A25A-FXS',null],
        ],
        // Toyota RAV4
        'RAV4' => [
            [2006,2012,'2AZ-FE',  'U151E'],
            [2013,2018,'2AR-FE',  'U760E'],
            [2019,2027,'A25A-FXS',null],
        ],
        // Honda Accord
        'Accord' => [
            [2003,2007,'K24A4',   null],
            [2008,2012,'K24Z3',   null],
            [2013,2017,'K24W',    null],
            [2018,2022,'K20C4',   null],
        ],
        // Honda Civic
        'Civic' => [
            [2006,2011,'R18A1',   null],
            [2012,2015,'R18Z1',   null],
            [2016,2021,'L15B7',   null],
        ],
        // Nissan Altima
        'Altima' => [
            [2007,2012,'QR25DE',  'RE0F09B'],
            [2013,2018,'QR25DE',  'RE0F10D'],
            [2019,2024,'KR20DDET',null],
        ],
    ];

    // Nigerian market pin counts for transmissions
    const PIN_COUNTS = [
        'U341E' => 13,  // Toyota Corolla 1.8L 4cyl
        'U760E' => 22,  // Toyota Camry V6
        'U660E' => 22,  // Toyota Camry V6 alt
        'A750E' => 22,  // Toyota Camry V6 auto
        'U241E' => 13,  // Toyota Camry 2.4L 4cyl
        'U151E' => 13,  // Toyota RAV4 2.4L
        'U151F' => 13,  // RAV4 4WD
        'RE0F09B' => 13, // Nissan CVT
        'RE0F10D' => 13, // Nissan CVT
    ];

    const HARVEST_TEMPLATE = [
        'Engine & Drivetrain' => [
            ['key'=>'engine',       'label'=>'Engine',        'category'=>'Engine'],
            ['key'=>'transmission', 'label'=>'Transmission',  'category'=>'Transmission'],
            ['key'=>'alternator',   'label'=>'Alternator',    'category'=>'Electrical'],
            ['key'=>'starter',      'label'=>'Starter',       'category'=>'Electrical'],
            ['key'=>'fuel_pump',    'label'=>'Fuel Pump',     'category'=>'Fuel'],
            ['key'=>'air_mass_box', 'label'=>'Air Mass Box',  'category'=>'Engine'],
        ],
        'CV Axles' => [
            ['key'=>'cv_axle_ps','label'=>'CV Axle P/S','category'=>'Suspension','side'=>'P/S'],
            ['key'=>'cv_axle_ds','label'=>'CV Axle D/S','category'=>'Suspension','side'=>'D/S'],
        ],
        'Cooling System' => [
            ['key'=>'radiator',    'label'=>'Radiator',     'category'=>'Cooling'],
            ['key'=>'condenser',   'label'=>'Condenser',    'category'=>'Cooling'],
            ['key'=>'fan_assembly','label'=>'Fan Assembly', 'category'=>'Cooling'],
        ],
        'Body — Front' => [
            ['key'=>'hood',             'label'=>'Hood',                'category'=>'Body'],
            ['key'=>'front_bumper_cover','label'=>'Front Bumper Cover', 'category'=>'Body'],
            ['key'=>'front_bumper_assy','label'=>'Front Bumper Assembly','category'=>'Body'],
            ['key'=>'grille',           'label'=>'Grille',              'category'=>'Body'],
            ['key'=>'headlight_ds',     'label'=>'Front Headlight D/S', 'category'=>'Body','side'=>'D/S'],
            ['key'=>'headlight_ps',     'label'=>'Front Headlight P/S', 'category'=>'Body','side'=>'P/S'],
        ],
        'Body — Doors' => [
            ['key'=>'door_front_ds','label'=>'Door Front D/S','category'=>'Body','side'=>'D/S'],
            ['key'=>'door_front_ps','label'=>'Door Front P/S','category'=>'Body','side'=>'P/S'],
            ['key'=>'door_rear_ds', 'label'=>'Door Rear D/S', 'category'=>'Body','side'=>'D/S'],
            ['key'=>'door_rear_ps', 'label'=>'Door Rear P/S', 'category'=>'Body','side'=>'P/S'],
            ['key'=>'lid_gate',     'label'=>'Lid / Tailgate','category'=>'Body'],
        ],
        'Body — Rear' => [
            ['key'=>'rear_bumper_cover','label'=>'Rear Bumper Cover','category'=>'Body'],
            ['key'=>'tail_light_ds',   'label'=>'Tail Light D/S',   'category'=>'Body','side'=>'D/S'],
            ['key'=>'tail_light_ps',   'label'=>'Tail Light P/S',   'category'=>'Body','side'=>'P/S'],
        ],
        'Suspension & Steering' => [
            ['key'=>'strut_ds',      'label'=>'Strut D/S',        'category'=>'Suspension','side'=>'D/S'],
            ['key'=>'strut_ps',      'label'=>'Strut P/S',        'category'=>'Suspension','side'=>'P/S'],
            ['key'=>'shock_absorber','label'=>'Shock Absorber',   'category'=>'Suspension'],
            ['key'=>'lower_ctrl_arm','label'=>'Lower Control Arm','category'=>'Suspension'],
        ],
        'Wheels & Tyres' => [
            ['key'=>'tires',  'label'=>'Tyres (set)', 'category'=>'Wheels'],
            ['key'=>'wheels', 'label'=>'Wheels (set)','category'=>'Wheels'],
        ],
        'Mirrors' => [
            ['key'=>'mirror_ds','label'=>'Mirror D/S','category'=>'Body','side'=>'D/S'],
            ['key'=>'mirror_ps','label'=>'Mirror P/S','category'=>'Body','side'=>'P/S'],
        ],
        'Interior — Seats (Front)' => [
            ['key'=>'seat_f_sdn_cloth_man_lh', 'label'=>'Sedan – Cloth – Manual – LH',  'category'=>'Seat','side'=>'D/S'],
            ['key'=>'seat_f_sdn_cloth_man_rh', 'label'=>'Sedan – Cloth – Manual – RH',  'category'=>'Seat','side'=>'P/S'],
            ['key'=>'seat_f_sdn_cloth_elec_lh','label'=>'Sedan – Cloth – Electric – LH','category'=>'Seat','side'=>'D/S'],
            ['key'=>'seat_f_sdn_cloth_elec_rh','label'=>'Sedan – Cloth – Electric – RH','category'=>'Seat','side'=>'P/S'],
            ['key'=>'seat_f_sdn_leath_elec_jp_lh','label'=>'Sedan – Leather – Electric – Japan – LH','category'=>'Seat','side'=>'D/S','origin'=>'Japan Built'],
            ['key'=>'seat_f_sdn_leath_elec_jp_rh','label'=>'Sedan – Leather – Electric – Japan – RH','category'=>'Seat','side'=>'P/S','origin'=>'Japan Built'],
            ['key'=>'seat_f_sdn_leath_elec_na_lh','label'=>'Sedan – Leather – Electric – NA Built – LH','category'=>'Seat','side'=>'D/S','origin'=>'North America Built'],
            ['key'=>'seat_f_sdn_leath_elec_na_rh','label'=>'Sedan – Leather – Electric – NA Built – RH','category'=>'Seat','side'=>'P/S','origin'=>'North America Built'],
        ],
        'Interior — Seats (Rear)' => [
            ['key'=>'seat_rear_2nd_row','label'=>'Seat Rear 2nd Row','category'=>'Seat'],
        ],
        'Airbags — Driver' => [
            ['key'=>'airbag_drv_knee', 'label'=>'Front Driver Knee',  'category'=>'Airbag','airbag_position'=>'Front Driver Knee'],
            ['key'=>'airbag_drv_roof', 'label'=>'Front Driver Roof',  'category'=>'Airbag','airbag_position'=>'Front Driver Roof'],
            ['key'=>'airbag_drv_seat', 'label'=>'Front Driver Seat',  'category'=>'Airbag','airbag_position'=>'Front Driver Seat'],
            ['key'=>'airbag_drv_wheel','label'=>'Front Driver Wheel', 'category'=>'Airbag','airbag_position'=>'Front Driver Wheel'],
        ],
        'Airbags — Passenger' => [
            ['key'=>'airbag_pax_dash', 'label'=>'Front Passenger Dash',  'category'=>'Airbag','airbag_position'=>'Front Passenger Dash'],
            ['key'=>'airbag_pax_knee', 'label'=>'Front Passenger Knee',  'category'=>'Airbag','airbag_position'=>'Front Passenger Knee'],
            ['key'=>'airbag_pax_roof', 'label'=>'Front Passenger Roof',  'category'=>'Airbag','airbag_position'=>'Front Passenger Roof'],
            ['key'=>'airbag_pax_seat', 'label'=>'Front Passenger Seat',  'category'=>'Airbag','airbag_position'=>'Front Passenger Seat'],
        ],
        'Airbags — Rear' => [
            ['key'=>'airbag_rear_lh','label'=>'Rear LH Airbag','category'=>'Airbag','airbag_position'=>'Rear LH','side'=>'D/S'],
            ['key'=>'airbag_rear_rh','label'=>'Rear RH Airbag','category'=>'Airbag','airbag_position'=>'Rear RH','side'=>'P/S'],
        ],
    ];

    const CODE_PREFIX = [
        'Engine'       => 'ENG',
        'Transmission' => 'TRN',
        'Electrical'   => 'ELC',
        'Fuel'         => 'FUL',
        'Cooling'      => 'CLG',
        'Body'         => 'BDY',
        'Suspension'   => 'SUS',
        'Wheels'       => 'WHL',
        'Seat'         => 'INT',
        'Airbag'       => 'AIR',
        'Interior'     => 'INT',
    ];

    // =========================================================
    // GET /admin/harvest
    // =========================================================
    public function index()
    {
        $sessions = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv','dv.id','=','hs.donor_vehicle_id')
            ->join('staff as s','s.id','=','hs.staff_id')
            ->select('hs.*','dv.make','dv.model','dv.year','dv.vin',
                     'dv.location','dv.id as dv_id','s.name as staff_name')
            ->orderByDesc('hs.created_at')
            ->paginate(20);

        return view('admin.harvest.index', compact('sessions'));
    }

    // =========================================================
    // GET /admin/harvest/create
    // =========================================================
    public function create()
    {
        return view('admin.harvest.create', [
            'staffLocation' => Session::get('staff_location'),
            'locations' => [
                'Waxahachie TX','Elkhorn WI',
                'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana'
            ],
        ]);
    }

    // =========================================================
    // POST /admin/harvest/vin-decode
    // =========================================================
    public function vinDecode(Request $request)
    {
        $request->validate(['vin' => 'required|string|size:17']);
        $vin = strtoupper(trim($request->vin));

        $cached = Cache::remember("nhtsa_{$vin}", now()->addDays(30), function () use ($vin) {
            try {
                $res = Http::timeout(8)->get(
                    "https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json"
                );
                if ($res->failed()) return null;
                $r = collect($res->json('Results', []));
                return [
                    'make'       => $r->firstWhere('Variable','Make')['Value']                ?? null,
                    'model'      => $r->firstWhere('Variable','Model')['Value']               ?? null,
                    'year'       => $r->firstWhere('Variable','Model Year')['Value']          ?? null,
                    'trim'       => $r->firstWhere('Variable','Trim')['Value']                ?? null,
                    'body_style' => $r->firstWhere('Variable','Body Class')['Value']          ?? null,
                    'engine'     => $r->firstWhere('Variable','Displacement (L)')['Value']    ?? null,
                    'drive_type' => $r->firstWhere('Variable','Drive Type')['Value']          ?? null,
                    'origin'     => $r->firstWhere('Variable','Plant Country')['Value']       ?? null,
                    'cylinders'  => $r->firstWhere('Variable','Engine Number of Cylinders')['Value'] ?? null,
                ];
            } catch (\Exception $e) { return null; }
        });

        if (!$cached || !$cached['make']) {
            return response()->json(['error' => 'Could not decode this VIN.'], 422);
        }

        // Auto-suggest OEM codes based on model + year
        $oemSuggestion = $this->suggestOemCodes($cached['model'] ?? '', (int)($cached['year'] ?? 0));
        $cached['oem_suggestion'] = $oemSuggestion;

        return response()->json(['vehicle' => $cached]);
    }

    // Suggest OEM engine/transmission codes from known table
    private function suggestOemCodes(string $model, int $year): array
    {
        foreach (self::OEM_CODES as $knownModel => $codes) {
            if (stripos($model, $knownModel) !== false) {
                foreach ($codes as [$from, $to, $eng, $trns]) {
                    if ($year >= $from && $year <= $to) {
                        return [
                            'engine_code'      => $eng,
                            'transmission_code'=> $trns,
                            'pin_count'        => $trns ? (self::PIN_COUNTS[$trns] ?? null) : null,
                        ];
                    }
                }
            }
        }
        return [];
    }

    // =========================================================
    // POST /admin/harvest — register donor vehicle + start session
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'vin'       => 'required|string|size:17',
            'make'      => 'required|string|max:60',
            'model'     => 'required|string|max:80',
            'year'      => 'required|integer|min:1986|max:2027',
            'mileage'   => 'required|integer|min:0|max:9999999',
            'condition' => 'required|in:Good,Fair,Poor',
            'source'    => 'required|in:Auction,Insurance,Private Sale,Dealer,Other',
            'location'  => 'required|string|max:60',
        ]);

        $dvId = DB::table('donor_vehicles')->insertGetId([
            'vin'             => strtoupper($request->vin),
            'year'            => $request->year,
            'make'            => $request->make,
            'model'           => $request->model,
            'trim'            => $request->trim,
            'colour'          => $request->colour,
            'mileage'         => $request->mileage,
            'date_acquired'   => today(),
            'source'          => $request->source,
            'condition'       => $request->condition,
            'location'        => $request->location,
            'parts_harvested' => 0,
            'notes'           => $request->notes,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $sessionId = DB::table('harvest_sessions')->insertGetId([
            'donor_vehicle_id' => $dvId,
            'staff_id'         => Session::get('staff_id'),
            'status'           => 'in_progress',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.harvest.checklist', $sessionId)
            ->with('success', 'Donor vehicle registered. Tick the parts harvested below.');
    }

    // =========================================================
    // GET /admin/harvest/{session}/checklist
    // =========================================================
    public function checklist(int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv','dv.id','=','hs.donor_vehicle_id')
            ->where('hs.id', $sessionId)
            ->select('hs.*','dv.make','dv.model','dv.year','dv.vin',
                     'dv.mileage','dv.colour','dv.body_style','dv.location','dv.id as donor_id')
            ->first();

        if (!$session) abort(404);

        $existing = DB::table('parts_inventory')
            ->where('donor_vin', $session->vin)->pluck('part_name')->toArray();

        // Get OEM suggestions for this vehicle
        $oem = $this->suggestOemCodes($session->model, (int)$session->year);
        $ngnRate = Cache::get('exchange_rates.NGN', 1600.0);

        return view('admin.harvest.checklist', [
            'session'    => $session,
            'template'   => self::HARVEST_TEMPLATE,
            'existing'   => $existing,
            'ngnRate'    => $ngnRate,
            'oemSuggest' => $oem,
        ]);
    }

    // =========================================================
    // POST /admin/harvest/{session}/parts — save parts to inventory
    // =========================================================
    public function saveParts(Request $request, int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv','dv.id','=','hs.donor_vehicle_id')
            ->where('hs.id', $sessionId)
            ->select('hs.*','dv.make','dv.model','dv.year','dv.vin',
                     'dv.mileage','dv.colour','dv.body_style','dv.location','dv.id as donor_id')
            ->first();

        if (!$session) abort(404);

        $parts   = $request->input('parts', []);
        $prices  = $request->input('prices', []);
        $grades  = $request->input('grades', []);
        $notes   = $request->input('part_notes', []);
        $oemEng  = $request->input('oem_engine', []);
        $oemTrns = $request->input('oem_transmission', []);
        $pins    = $request->input('pin_count', []);

        if (empty($parts)) {
            return back()->with('error', 'Please tick at least one part before saving.');
        }

        $ngnRate      = Cache::get('exchange_rates.NGN', 1600.0);
        $created      = 0;
        $flatTemplate = collect(self::HARVEST_TEMPLATE)->flatten(1);

        // Suggest OEM codes for this vehicle
        $oemSuggest = $this->suggestOemCodes($session->model, (int)$session->year);

        DB::beginTransaction();
        try {
            foreach ($parts as $key) {
                $tpl = $flatTemplate->firstWhere('key', $key);
                if (!$tpl) continue;

                $priceUsd = (float) ($prices[$key] ?? 0);
                if ($priceUsd <= 0) continue;

                $grade    = $grades[$key] ?? 'B';
                $partNote = $notes[$key] ?? null;

                // OEM codes — from form input or auto-suggested
                $engineCode = null;
                $transCode  = null;
                $pinCount   = null;
                $gearAlias  = null;

                if ($tpl['category'] === 'Engine') {
                    $engineCode = strtoupper(trim($oemEng[$key] ?? $oemSuggest['engine_code'] ?? '')) ?: null;
                }
                if ($tpl['category'] === 'Transmission') {
                    $transCode = strtoupper(trim($oemTrns[$key] ?? $oemSuggest['transmission_code'] ?? '')) ?: null;
                    $pinCount  = (int) ($pins[$key] ?? $oemSuggest['pin_count'] ?? 0) ?: null;
                    if ($pinCount) {
                        $gearAlias = "{$pinCount}-pin gear";
                        if ($session->model) $gearAlias .= " ({$session->make} {$session->model})";
                    }
                }

                // Compatibility years — default to ±2 years for body parts
                $compatFrom = (int)$session->year;
                $compatTo   = (int)$session->year;
                if (in_array($tpl['category'], ['Body','Interior','Seat'])) {
                    // Body panels typically share 3–5 year generations
                    $compatFrom = max(1986, $compatFrom - 2);
                    $compatTo   = min(2027, $compatTo + 2);
                }

                // Generate part code
                $prefix  = self::CODE_PREFIX[$tpl['category']] ?? 'PRT';
                $lastCode = DB::table('parts_inventory')
                    ->where('part_code','like',$prefix.'-%')
                    ->orderByDesc('id')->value('part_code');
                $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix)+1) + 1 : 1;
                $partCode = $prefix.'-'.str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                DB::table('parts_inventory')->insert([
                    'part_code'              => $partCode,
                    'brand'                  => $session->make,
                    'model'                  => $session->model,
                    'year_from'              => $session->year,
                    'year_to'                => $session->year,
                    'compat_year_from'       => $compatFrom,
                    'compat_year_to'         => $compatTo,
                    'part_name'              => $tpl['label'],
                    'part_category'          => $tpl['category'],
                    'side'                   => $tpl['side'] ?? 'N/A',
                    'airbag_position'        => $tpl['airbag_position'] ?? null,
                    'seat_type'              => $tpl['category']==='Seat' ? $tpl['label'] : null,
                    'origin'                 => $tpl['origin'] ?? 'N/A',
                    'body_style'             => $session->body_style ? substr($session->body_style,0,60) : null,
                    'donor_vin'              => $session->vin,
                    'mileage'                => $session->mileage,
                    'condition_grade'        => $grade,
                    'engine_code_oem'        => $engineCode,
                    'transmission_code_oem'  => $transCode,
                    'pin_count'              => $pinCount,
                    'gear_alias'             => $gearAlias,
                    'origin_market'          => 'N/A',
                    'price_usd'              => $priceUsd,
                    'location'               => $session->location,
                    'stock_qty'              => 1,
                    'status'                 => 'Available',
                    'description'            => $partNote,
                    'photos'                 => '[]',
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                $created++;
            }

            DB::table('harvest_sessions')->where('id', $sessionId)->update([
                'parts_harvested' => count($parts),
                'parts_listed'    => $created,
                'status'          => 'completed',
                'completed_at'    => now(),
                'notes'           => $request->input('session_notes'),
                'updated_at'      => now(),
            ]);

            DB::table('donor_vehicles')->where('id', $session->donor_id)->update([
                'parts_harvested' => $created,
                'updated_at'      => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.harvest.complete', $sessionId)
                ->with('success', "{$created} parts listed successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Harvest saveParts failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // =========================================================
    // GET /admin/harvest/{session}/complete
    // =========================================================
    public function complete(int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv','dv.id','=','hs.donor_vehicle_id')
            ->join('staff as s','s.id','=','hs.staff_id')
            ->where('hs.id', $sessionId)
            ->select('hs.*','dv.make','dv.model','dv.year','dv.vin','dv.location','s.name as staff_name')
            ->first();

        $newParts = DB::table('parts_inventory')
            ->where('donor_vin', $session->vin)
            ->orderBy('part_category')
            ->get();

        return view('admin.harvest.complete', compact('session','newParts'));
    }
}
