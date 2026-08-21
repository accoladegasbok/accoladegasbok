<?php
// FILE: database/migrations/2026_08_21_000000_add_revision_number_to_invoices.php
//
// Every save via updateManual() increments this, so an edited receipt
// can print "REV 2", "REV 3", etc. instead of looking identical to
// the original. Starts at 1 (the original, unedited receipt is
// implicitly "Rev 1" but we only print the badge once revision_number
// > 1, so a never-edited receipt looks exactly as it does today).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'revision_number')) {
                $table->unsignedInteger('revision_number')->default(1)->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'revision_number')) {
                $table->dropColumn('revision_number');
            }
        });
    }
};
