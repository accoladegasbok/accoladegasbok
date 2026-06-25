<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── staff.role was very likely still an ENUM('admin','manager',
        // 'staff','viewer') from before supervisor/stocking_clerk/
        // sales_rep existed. Switching anyone to/from a newer role
        // would throw a truncation error — the exact symptom reported.
        // Widen to a plain VARCHAR so this never happens again, no
        // matter how many roles get added later.
        Schema::table('staff', function (Blueprint $table) {
            $table->string('role', 30)->change();
        });

        // ── Ensure the percent/commission columns are generously
        // sized decimals, not something like TINYINT that could choke
        // on a value or a leftover '%' character making it through.
        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('discount_cap_percent', 5, 2)->nullable()->change();
            $table->decimal('commission_base_percent', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally no-op — reverting to a restrictive ENUM would
        // immediately break any staff already on a newer role.
    }
};
