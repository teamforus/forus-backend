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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('bank_reservation_first_name')->default(true)->after('bank_reservation_number');
            $table->boolean('bank_reservation_last_name')->default(true)->after('bank_reservation_first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['bank_reservation_first_name',  'bank_reservation_last_name']);
        });
    }
};
