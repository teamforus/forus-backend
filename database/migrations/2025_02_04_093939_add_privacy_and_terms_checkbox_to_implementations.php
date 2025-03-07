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
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('show_privacy_checkbox')->default(false)->after('show_product_map');
            $table->boolean('show_terms_checkbox')->default(false)->after('show_privacy_checkbox');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn(['show_privacy_checkbox', 'show_terms_checkbox']);
        });
    }
};
