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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->unsignedInteger('bsn_confirmation_time')
                ->after('limit_generator_amount')
                ->default(900)
                ->nullable();

            $table->unsignedInteger('bsn_confirmation_api_time')
                ->after('bsn_confirmation_time')
                ->default(900)
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('bsn_confirmation_time');
            $table->dropColumn('bsn_confirmation_api_time');
        });
    }
};
