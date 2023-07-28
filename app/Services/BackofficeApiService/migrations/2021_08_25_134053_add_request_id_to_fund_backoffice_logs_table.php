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
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->string('request_id', 200)->nullable()->after('state');
            $table->integer('voucher_id')->nullable()->after('bsn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->dropColumn('request_id', 'voucher_id');
        });
    }
};
