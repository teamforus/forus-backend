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
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->boolean('allow_fund_requests')->default(true)->after('allow_physical_cards');
            $table->boolean('allow_prevalidations')->default(true)->after('allow_fund_requests');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->dropColumn('allow_fund_requests');
            $table->dropColumn('allow_prevalidations');
        });
    }
};
