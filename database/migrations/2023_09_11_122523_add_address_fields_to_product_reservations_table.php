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
        Schema::table('product_reservations', function (Blueprint $table) {
            $table->string('street', 100)->nullable()->after('address');
            $table->string('house_nr', 20)->nullable()->after('street');
            $table->string('postal_code', 10)->nullable()->after('house_nr');
            $table->string('city', 50)->nullable()->after('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('product_reservations', function (Blueprint $table) {
            $table->dropColumn('street');
            $table->dropColumn('house_nr');
            $table->dropColumn('postal_code');
            $table->dropColumn('city');
        });
    }
};
