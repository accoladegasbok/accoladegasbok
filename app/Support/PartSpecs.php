<?php
// FILE: app/Support/PartSpecs.php
//
// Decides which specification fields a customer sees for a part, so they can
// confirm compatibility without guessing. The part TYPE decides the fields:
//
//   engine        engine code, layout (I4 / V6), displacement (2.4L), cylinders
//   transmission  gearbox code, type, speeds, PIN COUNT, drive type, paired engine
//   drivetrain    drive type, position              (differentials, axles, CV shafts)
//   chassis       DRIVE TYPE (2WD / 4WD / AWD ...), position, body style  (suspension, brakes, steering)
//   body          position (left / right), body style, trim, lighting type
//   electrical    engine code (modules are engine-specific), OE number is shown elsewhere
//   other         nothing extra
//
// Pin count is ONLY for gearbox parts. Drive type shows on every suspension part.
// Values come from what staff recorded on the part. Nothing is guessed from the
// car's model; a missing identity field shows as "Not recorded - ask us" on the
// part page and is simply left out of the small badges on search results.

namespace App\Support;

use App\Data\EngineSpecs;
use App\Data\OemDatabase;

class PartSpecs
{
    public static function basis(object $part): string
    {
        $cat  = strtolower((string) ($part->part_category ?? ''));
        $name = strtolower((string) ($part->part_name ?? ''));

        $isGearWord   = (bool) preg_match('/transmission|gearbox|gear box|torque converter|valve body|shift solenoid|\btcm\b|\bgear\b/', $name);
        $isEngineWord = (bool) preg_match('/engine|long block|short block|motor assembly/', $name);

        if ($isEngineWord && $isGearWord) return 'engine_gear';   // Complete Engine And Gear With Accessories

        if ($cat === 'engine')       return 'engine';
        if ($cat === 'transmission') {
            if (preg_match('/differential|axle|cv joint|cv boot|drive ?shaft|propeller/', $name)) return 'drivetrain';
            return 'transmission';
        }
        if (in_array($cat, ['suspension', 'brakes'], true)) return 'chassis';
        if ($cat === 'body' || $cat === 'airbag') return 'body';
        if (in_array($cat, ['electrical'], true)) return 'electrical';
        if (in_array($cat, ['cooling', 'fuel', 'exhaust'], true)) return 'engine_related';
        if (in_array($cat, ['interior', 'seat'], true)) return 'body';
        return 'other';
    }

    /**
     * Ordered fields: each = ['key','label','value'(null when not recorded),'tone','important'(bool)]
     * tone is used for the small badges: code | dark | pin | drive | plain
     */
    public static function fields(object $part): array
    {
        $basis = self::basis($part);
        $f = [];

        $engineCode = self::clean($part->engine_code_oem ?? null);
        $gearCode   = self::clean($part->transmission_code_oem ?? null);
        $drive      = self::driveType($part);
        $spec       = EngineSpecs::forCode($engineCode);

        // ---- engine block of fields
        $addEngine = function (bool $important) use (&$f, $part, $engineCode, $spec) {
            $litres = null;
            if (!empty($part->engine_displacement) && (float) $part->engine_displacement > 0) $litres = (float) $part->engine_displacement;
            elseif ($spec) $litres = $spec['litres'];
            elseif ($engineCode) $litres = OemDatabase::displacementForCode($engineCode);

            $cyl = !empty($part->cylinders) ? (int) $part->cylinders : ($spec['cylinders'] ?? null);
            $layout = self::clean($part->engine_config ?? null) ?: ($spec['layout'] ?? EngineSpecs::layoutFromCylinders($cyl, $part->brand ?? null));

            $f[] = ['key' => 'engine_code', 'label' => 'Engine code',   'value' => $engineCode, 'tone' => 'code', 'important' => $important];
            $f[] = ['key' => 'layout',      'label' => 'Configuration', 'value' => $layout,     'tone' => 'dark', 'important' => false];
            $f[] = ['key' => 'litres',      'label' => 'Displacement',  'value' => $litres ? rtrim(rtrim(number_format($litres, 1), '0'), '.') . 'L' : null, 'tone' => 'dark', 'important' => false];
            $f[] = ['key' => 'cylinders',   'label' => 'Cylinders',     'value' => $cyl ? (string) $cyl : null, 'tone' => 'plain', 'important' => false, 'table_only' => true];
        };

        // ---- gearbox block of fields
        $addGear = function () use (&$f, $part, $gearCode, $drive, $engineCode, $spec) {
            $f[] = ['key' => 'gear_code', 'label' => 'Gearbox code', 'value' => $gearCode, 'tone' => 'code', 'important' => true];
            $f[] = ['key' => 'gear_type', 'label' => 'Type',         'value' => self::gearType($part), 'tone' => 'plain', 'important' => false];
            $speeds = self::speeds($part);
            $f[] = ['key' => 'speeds',    'label' => 'Speeds',       'value' => $speeds ? $speeds . '-speed' : null, 'tone' => 'plain', 'important' => false];
            $pins = !empty($part->pin_count) ? (int) $part->pin_count : null;
            $f[] = ['key' => 'pins',      'label' => 'Pin count',    'value' => $pins ? $pins . '-pin' : null, 'tone' => 'pin', 'important' => true];
            $f[] = ['key' => 'drive',     'label' => 'Drive type',   'value' => $drive, 'tone' => 'drive', 'important' => true];
        };

        switch ($basis) {
            case 'engine':
                $addEngine(true);
                break;

            case 'engine_gear':
                $addEngine(true);
                $addGear();
                break;

            case 'transmission':
                $addGear();
                if ($engineCode) {
                    $label = $engineCode . ($spec ? ' · ' . ($spec['layout']) . ' · ' . rtrim(rtrim(number_format($spec['litres'], 1), '0'), '.') . 'L' : '');
                    $f[] = ['key' => 'paired_engine', 'label' => 'Paired engine', 'value' => $label, 'tone' => 'plain', 'important' => false, 'table_only' => true];
                }
                break;

            case 'drivetrain':
                $f[] = ['key' => 'drive', 'label' => 'Drive type', 'value' => $drive, 'tone' => 'drive', 'important' => true];
                $f[] = ['key' => 'position', 'label' => 'Position', 'value' => self::position($part), 'tone' => 'plain', 'important' => false];
                break;

            case 'chassis':
                $f[] = ['key' => 'drive', 'label' => 'Drive type', 'value' => $drive, 'tone' => 'drive', 'important' => true];
                $f[] = ['key' => 'position', 'label' => 'Position', 'value' => self::position($part), 'tone' => 'plain', 'important' => false];
                $f[] = ['key' => 'body', 'label' => 'Body style', 'value' => self::clean($part->body_style ?? null), 'tone' => 'plain', 'important' => false];
                break;

            case 'body':
                $f[] = ['key' => 'position', 'label' => 'Position', 'value' => self::position($part), 'tone' => 'plain', 'important' => false];
                $f[] = ['key' => 'body', 'label' => 'Body style', 'value' => self::clean($part->body_style ?? null), 'tone' => 'plain', 'important' => false];
                $f[] = ['key' => 'trim', 'label' => 'Trim', 'value' => self::clean($part->donor_trim ?? null), 'tone' => 'plain', 'important' => false];
                $f[] = ['key' => 'lighting', 'label' => 'Lighting', 'value' => self::lighting($part), 'tone' => 'plain', 'important' => false];
                break;

            case 'electrical':
            case 'engine_related':
                if ($engineCode) {
                    $f[] = ['key' => 'engine_code', 'label' => 'Engine code', 'value' => $engineCode, 'tone' => 'code', 'important' => false];
                    if ($spec) {
                        $f[] = ['key' => 'layout', 'label' => 'Configuration', 'value' => $spec['layout'], 'tone' => 'dark', 'important' => false];
                        $f[] = ['key' => 'litres', 'label' => 'Displacement', 'value' => rtrim(rtrim(number_format($spec['litres'], 1), '0'), '.') . 'L', 'tone' => 'dark', 'important' => false];
                    }
                }
                break;
        }
        return $f;
    }

    /** The small badges for search result cards: only fields that have a value. */
    public static function chips(object $part): array
    {
        return array_values(array_filter(self::fields($part), fn($x) => $x['value'] !== null && empty($x['table_only'])));
    }

    /** Rows for the part page: every field, with a clear "ask us" for missing important ones. */
    public static function rows(object $part): array
    {
        $out = [];
        foreach (self::fields($part) as $x) {
            if ($x['value'] === null && !$x['important']) continue;
            $out[] = ['label' => $x['label'], 'value' => $x['value'], 'missing' => $x['value'] === null];
        }
        return $out;
    }

    // ------------------------------------------------------------------ helpers

    private static function clean($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;
        if ($v === null || $v === '' || strtoupper((string) $v) === 'N/A') return null;
        return (string) $v;
    }

    /** Stored drive type; falls back to a drive word inside the gear alias (existing behaviour). */
    private static function driveType(object $part): ?string
    {
        $d = self::clean($part->drive_type ?? null);
        if ($d) return $d;
        $alias = strtoupper((string) ($part->gear_alias ?? ''));
        foreach (['4WD', '4X4', 'AWD', '2WD', '4X2', 'FWD', 'RWD'] as $w) {
            if ($alias !== '' && str_contains($alias, $w)) return $w === '4X4' ? '4x4' : ($w === '4X2' ? '4x2' : $w);
        }
        return null;
    }

    private static function gearType(object $part): ?string
    {
        $n = strtolower(($part->part_name ?? '') . ' ' . ($part->gear_alias ?? ''));
        if (str_contains($n, 'cvt')) return 'CVT';
        if (str_contains($n, 'manual')) return 'Manual';
        if (str_contains($n, 'automatic') || preg_match('/\bauto\b|\b\dat\b/', $n)) return 'Automatic';
        return null;
    }

    private static function speeds(object $part): ?int
    {
        if (!empty($part->transmission_speeds)) return (int) $part->transmission_speeds;
        $t = (string) ($part->gear_alias ?? '');
        if (preg_match('/(\d{1,2})\s*[- ]?\s*(?:spd|speed)\b/i', $t, $m)) return (int) $m[1];
        if (preg_match('/\b(\d{1,2})AT\b/i', $t, $m)) return (int) $m[1];
        return null;
    }

    private static function position(object $part): ?string
    {
        $side = self::clean($part->side ?? null);
        return $side;
    }

    private static function lighting(object $part): ?string
    {
        $v = self::clean($part->lighting_type ?? null);
        if ($v) return $v;
        $n = strtolower((string) ($part->part_name ?? ''));
        foreach (['halogen' => 'Halogen', 'xenon' => 'HID / Xenon', 'hid' => 'HID / Xenon', 'led' => 'LED'] as $k => $label) {
            if (preg_match('/\b' . $k . '\b/', $n)) return $label;
        }
        return null;
    }
}
