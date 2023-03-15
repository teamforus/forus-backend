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
            $table->string('phone', 50)->nullable()->after('last_name');
            $table->string('address', 100)->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('address');
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
            $table->dropColumn('phone');
            $table->dropColumn('address');
            $table->dropColumn('birth_date');
        });
    }
};
