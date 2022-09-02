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
            $table->boolean('hash_partner_deny')->default(false)->after('hash_bsn_salt');
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
            $table->dropColumn('hash_partner_deny');
        });
    }
};
