<?php
// FILE: app/Support/VehicleBrands.php
//
// SINGLE SOURCE OF TRUTH for every vehicle brand the business currently
// deals in. Same pattern as App\Support\Locations — one canonical list
// that every form/controller references, instead of each one keeping
// its own copy (which is exactly what caused the Lagos location bug).
//
// InventoryController::BRANDS currently duplicates this list — worth
// pointing that constant at VehicleBrands::all() too, next time that
// file is touched, so there's truly only one copy.

namespace App\Support;

class VehicleBrands
{
    /**
     * Canonical list, in display order. Add new brands here ONLY —
     * every form (parts inventory, car sales receipt, etc.) reads
     * from this single list.
     */
    const ALL = [
        'Toyota', 'Lexus', 'Kia', 'Hyundai', 'Nissan', 'Mercedes-Benz',
        'Infiniti', 'Ford', 'GM', 'Chevrolet', 'Acura', 'VW', 'Honda',
    ];

    public static function all(): array
    {
        return self::ALL;
    }
}
