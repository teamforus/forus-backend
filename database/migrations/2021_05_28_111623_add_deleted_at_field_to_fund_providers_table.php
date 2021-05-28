<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddDeletedAtFieldToFundProvidersTable
 * @noinspection PhpUnused
 */
class AddDeletedAtFieldToFundProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->softDeletes()->after('dismissed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
