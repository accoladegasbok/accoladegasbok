<?php
// FILE: app/Http/Middleware/RestrictStockingClerk.php
//
// Stocking Clerk role is restricted to ONLY: New Harvest, Add Part
// Manually, Add Consumable — no editing, no pricing changes, no
// invoices, no reports, no customers. Everything else 403s.
//
// Register this in bootstrap/app.php (Laravel 11+) as a route
// middleware alias, e.g.:
//
//   ->withMiddleware(function (Middleware $middleware) {
//       $middleware->alias([
//           'stocking-clerk' => \App\Http\Middleware\RestrictStockingClerk::class,
//       ]);
//   })
//
// Then apply it to the WHOLE admin.auth protected group in
// routes/admin.php, right alongside the existing admin.auth
// middleware, so it runs on every admin request and silently
// allows non-stocking_clerk roles through untouched.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictStockingClerk
{
    // Route NAMES (or wildcard patterns) a stocking_clerk may access.
    // Everything else returns 403.
    private const ALLOWED_ROUTE_PATTERNS = [
        'admin.dashboard',
        'admin.logout',

        // New Harvest flow — full path needed to register a donor
        // vehicle and run the harvest checklist.
        'admin.harvest.create',
        'admin.harvest.store',
        'admin.harvest.vin-decode',
        'admin.harvest.search-donors',
        'admin.harvest.engine-options',
        'admin.harvest.checklist',
        'admin.harvest.saveParts',
        'admin.harvest.complete',

        // Add Part Manually + Add Consumable
        'admin.inventory.manual-add',
        'admin.inventory.create',
        'admin.inventory.store',
        'admin.inventory.oem-lookup',
        'admin.inventory.consumable.create',
        'admin.inventory.consumable.store',

        // Needed by the bin-location dropdown on the forms above
        'admin.storage.rooms-for-location',
        'admin.storage.shelves-for-room',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Session::get('staff_role') !== 'stocking_clerk') {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && Str::is(self::ALLOWED_ROUTE_PATTERNS, $routeName)) {
            return $next($request);
        }

        abort(403, 'Stocking clerks can only add new inventory (harvest, manual add, or consumables).');
    }
}
