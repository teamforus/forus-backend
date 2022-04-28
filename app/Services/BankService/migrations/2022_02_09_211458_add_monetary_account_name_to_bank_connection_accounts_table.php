<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class AddMonetaryAccountNameToBankConnectionAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('bank_connection_accounts', function (Blueprint $table) {
            $table->string('monetary_account_id', 200)->change();
            $table->string('monetary_account_name', 200)->nullable()->after('monetary_account_iban');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bank_connection_accounts', function (Blueprint $table) {
            $table->dropColumn('monetary_account_name');
        });
    }
}
