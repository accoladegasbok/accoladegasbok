<?php
// FILE: app/Support/Contacts.php
//
// The ONE place for Auto Zenith phone / WhatsApp numbers. Change a number here and it changes
// on the website, the Text-us box, receipts, labels, policy pages and WhatsApp links.

namespace App\Support;

class Contacts
{
    /** key => [label shown to people, number in international form, digits-only for wa.me / tel:]. */
    public const LINES = [
        'usa'       => ['label' => 'USA',                          'name' => 'Auto Zenith USA',   'number' => '+15125873425', 'email' => 'usa@autozenithparts.com', 'address' => 'Waxahachie TX · Kennedale TX · 613 E Geneva St #23, Elkhorn WI'],
        'lagos'     => ['label' => 'Lagos (main West Africa hub)', 'name' => 'Auto Zenith 1 LA',  'number' => '+2349155688804', 'email' => 'ng@autozenithparts.com', 'address' => 'No. 3 Aimasiko Street, Shop 1 (Apaku Mall), Mafoluku, Oshodi, Lagos State'],
        'accra'     => ['label' => 'Accra, Ghana',                 'name' => 'Accra hub',         'number' => '+233201819789', 'email' => 'gh@autozenithparts.com', 'address' => 'Accra, Ghana'],
        'ibadan'    => ['label' => 'Ibadan',                       'name' => 'Auto Zenith 3 IB',  'number' => '+2348168024903', 'email' => 'ng@autozenithparts.com', 'address' => 'Auto Zenith Mall, Samadex Junction, off Iwo Road, behind Bishop Philips Academy, Ibadan, Oyo State'],
        'ife'       => ['label' => 'Ile-Ife regional hub',         'name' => 'Auto Zenith 2 FE',  'number' => '+2348136078367', 'email' => 'ng@autozenithparts.com', 'address' => 'No. 1 Suite B, Gasbok Engineering Avenue, Ibadan Road, Ile-Ife, Osun State'],
        'kaduna'    => ['label' => 'Kaduna / Jos / Makurdi',       'name' => 'Kaduna hub',        'number' => '+2348053066068', 'email' => 'ng@autozenithparts.com', 'address' => 'Kaduna / Jos / Makurdi'],
        'complaints'=> ['label' => 'Complaints and enquiries (WhatsApp only)', 'name' => 'Complaints', 'number' => '+2348067422777', 'email' => 'info@autozenithparts.com', 'address' => ''],
    ];

    /** Which line answers for a part / sale location. USA is the default. */
    public static function keyFor(?string $location): string
    {
        $l = strtolower((string) $location);
        if ($l === '') return 'usa';
        if (str_contains($l, 'ghana') || str_contains($l, 'accra'))                                  return 'accra';
        if (str_contains($l, 'ibadan'))                                                              return 'ibadan';
        if (str_contains($l, 'ife'))                                                                 return 'ife';
        if (str_contains($l, 'kaduna') || str_contains($l, 'jos') || str_contains($l, 'makurdi') || str_contains($l, 'markurdi')) return 'kaduna';
        // Lagos hub answers for Lagos and every other West Africa yard (Akure, Abuja, general Nigeria).
        if (str_contains($l, 'nigeria') || str_contains($l, 'lagos') || str_contains($l, 'oshodi')
            || str_contains($l, 'akure') || str_contains($l, 'abuja'))                               return 'lagos';
        return 'usa';
    }

    public static function line(string $key): array
    {
        return self::LINES[$key] ?? self::LINES['usa'];
    }

    /** "+2349155688804" */
    public static function numberFor(?string $location): string
    {
        return self::line(self::keyFor($location))['number'];
    }

    /** "2349155688804" — for https://wa.me/... */
    public static function waFor(?string $location): string
    {
        return ltrim(self::numberFor($location), '+');
    }

    public static function wa(string $key): string
    {
        return ltrim(self::line($key)['number'], '+');
    }

    /** Nicely spaced for printing: "+234 915 568 8804" / "+1 512 587 3425". */
    public static function pretty(string $number): string
    {
        $n = ltrim($number, '+');
        if (str_starts_with($n, '1') && strlen($n) === 11) {
            return '+1 ' . substr($n, 1, 3) . ' ' . substr($n, 4, 3) . ' ' . substr($n, 7);
        }
        if (str_starts_with($n, '234') && strlen($n) === 13) {
            return '+234 ' . substr($n, 3, 3) . ' ' . substr($n, 6, 3) . ' ' . substr($n, 9);
        }
        if (str_starts_with($n, '233') && strlen($n) === 12) {
            return '+233 ' . substr($n, 3, 2) . ' ' . substr($n, 5, 3) . ' ' . substr($n, 8);
        }
        return '+' . $n;
    }

    public static function prettyFor(?string $location): string
    {
        return self::pretty(self::numberFor($location));
    }

    /** Email for a location: usa@ / gh@ / ng@ (all Nigeria hubs); info@ is the general address. */
    public static function emailFor(?string $location): string
    {
        return self::line(self::keyFor($location))['email'];
    }

    public static function email(string $key): string
    {
        return self::line($key)['email'];
    }

    public const GENERAL_EMAIL = 'info@autozenithparts.com';

    public static function prettyKey(string $key): string
    {
        return self::pretty(self::line($key)['number']);
    }
}
