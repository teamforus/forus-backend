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
            $table->boolean('archived')->default(0)->after('note');
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
            $table->dropColumn('archived');
        });
    }
};