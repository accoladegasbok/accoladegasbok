<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Request this part": a customer who cannot find (or finds a sold) part leaves their details,
 * the vehicle and the part. Staff work the list from the admin inbox.
 * status: new -> contacted -> sourced -> closed
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('part_requests')) return;

        Schema::create('part_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 40);
            $table->string('customer_email', 150)->nullable();
            $table->string('vehicle_text', 255)->nullable();   // e.g. "2008 HONDA ACCORD, 2003 TOYOTA CAMRY"
            $table->string('part_text', 255);                  // e.g. "Alternator, Starter Motor"
            $table->text('notes')->nullable();
            $table->string('region', 40)->nullable();          // where the customer needs it: Lagos / West Africa, USA
            $table->string('source', 20)->default('search');   // search | part_page
            $table->unsignedBigInteger('part_id')->nullable(); // the sold/unavailable part they looked at
            $table->string('status', 20)->default('new')->index();
            $table->text('staff_notes')->nullable();
            $table->unsignedBigInteger('handled_by_staff_id')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_requests');
    }
};
