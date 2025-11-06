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
        Schema::rename('organization_reservation_fields', 'reservation_fields');

        Schema::table('product_reservation_field_values', function (Blueprint $table) {
            $table->renameColumn('organization_reservation_field_id', 'reservation_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('reservation_fields', 'organization_reservation_fields');

        Schema::table('product_reservation_field_values', function (Blueprint $table) {
            $table->renameColumn('reservation_field_id', 'organization_reservation_field_id');
        });
    }
};
