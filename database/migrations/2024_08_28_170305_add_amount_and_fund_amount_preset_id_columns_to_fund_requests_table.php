<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->decimal('amount')->nullable()->after('state');
            $table->unsignedBigInteger('fund_amount_preset_id')->nullable()->after('amount');


            $table
                ->foreign('fund_amount_preset_id')
                ->references('id')
                ->on('fund_amount_presets')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropForeign('fund_requests_fund_amount_preset_id_foreign');
        });

        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->dropColumn('fund_amount_preset_id');
        });
    }
};
