<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class AddAuthUrlAndAuthParamsToBankConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->string('access_token', 1000)->change();
            $table->string('consent_id', 200)->nullable()->after('bank_connection_account_id');
            $table->string('auth_url', 2000)->nullable()->after('consent_id');
            $table->json('auth_params')->nullable()->after('auth_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dropColumn('consent_id', 'auth_url', 'auth_params');
        });
    }
}
