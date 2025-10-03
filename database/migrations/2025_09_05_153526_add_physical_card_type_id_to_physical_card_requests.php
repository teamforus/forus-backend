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
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->unsignedInteger('physical_card_type_id')->nullable()->after('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->dropColumn('physical_card_type_id');
        });
    }
};
