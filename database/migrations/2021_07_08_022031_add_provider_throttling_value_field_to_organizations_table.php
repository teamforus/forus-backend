<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddProviderThrottlingValueFieldToOrganizationsTable
 * @noinspection PhpUnused
 */
class AddProviderThrottlingValueFieldToOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->integer('provider_throttling_value')
                ->after('allow_batch_reservations')
                ->default(100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('provider_throttling_value');
        });
    }
}
