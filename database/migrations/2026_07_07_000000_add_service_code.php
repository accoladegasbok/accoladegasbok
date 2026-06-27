<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_rates', function (Blueprint $table) {
            $table->string('service_code', 20)->nullable()->unique()->after('id');
        });

        // Backfill existing service rates with a generated code
        $services = DB::table('service_rates')->orderBy('id')->get();
        foreach ($services as $i => $s) {
            DB::table('service_rates')->where('id', $s->id)->update([
                'service_code' => 'SVC-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('service_rates', function (Blueprint $table) {
            $table->dropColumn('service_code');
        });
    }
};
