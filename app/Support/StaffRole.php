<?php
// FILE: app/Support/StaffRole.php
//
// One place for "who counts as supervisor and above". Before this, each
// controller hard-coded its own ['admin','manager'] list, which is why
// supervisors could not add part names even after that was decided.

namespace App\Support;

use Illuminate\Support\Facades\Session;

class StaffRole
{
    /** Roles that rank at or above supervisor. */
    public const SUPERVISOR_UP = ['admin', 'manager', 'supervisor'];

    public static function current(): ?string
    {
        return Session::get('staff_role');
    }

    public static function isSupervisorOrAbove(): bool
    {
        return in_array(self::current(), self::SUPERVISOR_UP, true);
    }

    public static function isAdmin(): bool
    {
        return self::current() === 'admin';
    }
}
