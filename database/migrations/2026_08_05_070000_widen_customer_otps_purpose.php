<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: same pattern as parts_inventory.brand and staff.location —
 * customer_otps.purpose was an ENUM limited to a fixed list
 * (register, login, change_email, change_phone, telegram_link).
 * Adding password_reset as a new purpose would hit the exact same
 * 1265 "Data truncated" error already fixed elsewhere. Widening to
 * VARCHAR so future purposes don't require a migration every time.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `customer_otps` MODIFY COLUMN `purpose` VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `customer_otps` MODIFY COLUMN `purpose` ENUM(
            'register','login','change_email','change_phone','telegram_link'
        ) NOT NULL");
    }
};
