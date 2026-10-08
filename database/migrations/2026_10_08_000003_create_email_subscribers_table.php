<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** People who asked, on the website, to get our emails (new arrivals, offers). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_subscribers')) return;

        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->unique();
            $table->string('source', 30)->default('site');      // home | site
            $table->string('token', 40)->unique();              // used in the unsubscribe link
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();   // set = do not email
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_subscribers');
    }
};
