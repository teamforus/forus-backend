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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('hash_bsn');
            $table->dropColumn('hash_bsn_salt');
            $table->renameColumn('hash_partner_deny', 'partner_deny');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('hash_bsn')->default(false)->after('record_validity_start_date');
            $table->string('hash_bsn_salt', 200)->nullable()->after('hash_bsn');
            $table->renameColumn('partner_deny', 'hash_partner_deny');
        });
    }
};
