<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $options = ['no', 'optional', 'required'];

            $table->enum('reservation_phone', $options)->default('no')->after('reservations_auto_accept');
            $table->enum('reservation_address', $options)->default('no')->after('reservation_phone');
            $table->enum('reservation_birth_date', $options)->default('no')->after('reservation_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('reservation_phone');
            $table->dropColumn('reservation_address');
            $table->dropColumn('reservation_birth_date');
        });
    }
};
