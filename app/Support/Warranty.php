<?php
// FILE: app/Support/Warranty.php
//
// THE one place the warranty wording lives. The home page, part search page,
// part page, receipt/invoice footer, invoice PDF and the /warranty page all
// read from here, so the terms can never drift apart again.
//
// Change a period or a condition HERE and every page follows.

namespace App\Support;

class Warranty
{
    public const URL_PATH = '/warranty';

    /** Short text printed at the bottom of invoices and receipts. */
    public static function short(): string
    {
        return 'Limited Warranty: Engines 90 days (USA) / 30 days (West Africa). '
            . 'Transmissions 90 days (USA); in West Africa 30 days only when installed by our recommended technician with our recommended oil. '
            . 'No warranty on transmissions bought and taken away; inspect properly before purchase. '
            . 'Mechanical parts 30 days. Electrical 14 days, exchange or credit only. Labor not covered. '
            . 'Void if the Auto Zenith tag or mark is removed or the part is disassembled. '
            . 'Returns within 7 days, uninstalled, 20% restocking fee. Full terms: autozenithparts.com/warranty';
    }

    /** One line for the home hero and product pages. */
    public static function trustLine(): string
    {
        return 'Engine warranty: 90 days (USA) / 30 days (West Africa)';
    }

    /** Text inside the round badge on the home page. */
    public static function badge(): array
    {
        return ['30-90', 'DAYS'];
    }

    /** The two paragraphs of the home page warranty band. HTML-safe (already escaped). */
    public static function bandParagraphs(): array
    {
        return [
            'Warranty starts on your invoice or receipt date and covers defects that stop a part from working normally. '
            . '<strong>Engines:</strong> 90 days (USA) / 30 days (West Africa). '
            . '<strong>Transmissions:</strong> 90 days (USA); in West Africa 30 days, only when installed by our recommended technician with our recommended oil &mdash; no warranty on transmissions bought and taken away. '
            . '<strong>Other mechanical parts:</strong> 30 days. '
            . '<strong>Electrical modules and sensors:</strong> 14 days, exchange or store credit only.',

            'Labor is not covered. The warranty is void if the Auto Zenith tag or mark is removed or the part is disassembled. '
            . 'Body panels, glass and lights must be inspected at purchase. '
            . 'Returns within 7 days, uninstalled, carry a 20% restocking fee.',
        ];
    }

    /** Short note for part pages and search results (plain text). */
    public static function partNote(?string $category = null): string
    {
        $c = strtolower(trim(explode(',', (string) $category)[0]));
        if ($c === 'engine')       return 'Engines: 90 days (USA) / 30 days (West Africa) from your invoice date.';
        if ($c === 'transmission') return 'Transmissions: 90 days (USA); 30 days in West Africa only when installed by our recommended technician with our recommended oil.';
        if (in_array($c, ['electrical'], true)) return 'Electrical and electronic parts: 14 days, exchange or store credit only.';
        if (in_array($c, ['body', 'interior', 'seat', 'wheels'], true)) return 'Body panels, glass, lights and interior: inspect when you receive it and report damage within 48 hours.';
        return 'Mechanical parts: 30 days from your invoice date. Engines 90 days (USA) / 30 days (West Africa).';
    }

    /**
     * Rows of the coverage table on the /warranty page.
     * 'both' rows span the USA and West Africa columns.
     */
    public static function rows(): array
    {
        return [
            ['part' => 'Complete engines (including Complete Engine, Bare / Borale)', 'usa' => '90 days', 'wa' => '30 days'],
            ['part' => 'Transmissions', 'usa' => '90 days', 'wa' => '30 days, only when installed by an Auto Zenith recommended technician using our recommended transmission oil'],
            ['part' => 'Other mechanical parts (alternators, starters, A/C compressors, power steering pumps, axles, suspension, brakes, differentials)', 'usa' => '30 days', 'wa' => '30 days'],
            ['part' => 'Electrical and electronic modules (ECU, TCM, ABS, BCM, sensors, screens, clusters)', 'usa' => '14 days — exchange or store credit only', 'wa' => '14 days — exchange or store credit only'],
            ['part' => 'Body panels, glass, lights, interior, trim', 'both' => 'Inspect when you receive it. Report damage within 48 hours. No defect warranty after that.'],
            ['part' => 'Consumables and new items', 'both' => "Manufacturer's warranty only"],
        ];
    }
}
