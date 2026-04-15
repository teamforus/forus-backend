<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_reservation_field_values', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('product_reservation_field_values')->update([
            'value' => DB::raw('LEFT(`value`, 191)'),
        ]);

        Schema::table('product_reservation_field_values', function (Blueprint $table) {
            $table->string('value', 191)->nullable()->change();
        });
    }
};
