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
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('reservation_fields', 'reservation_fields_enabled');

            $table->enum('reservation_fields_config', ['global', 'yes', 'no'])
                ->default('global')
                ->after('reservation_fields_enabled');
        });

        Schema::table('organization_reservation_fields', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->after('organization_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('reservation_fields_enabled', 'reservation_fields');
            $table->dropColumn('reservation_fields_config');
        });

        Schema::table('organization_reservation_fields', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
