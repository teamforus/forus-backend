<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_reservations', function (Blueprint $table) {
            $table->string('cancellation_note', 255)->nullable()->after('note');
            $table->string('rejection_note', 255)->nullable()->after('cancellation_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_reservations', function (Blueprint $table) {
            $table->dropColumn('rejection_note', 'cancellation_note');
        });
    }
};
