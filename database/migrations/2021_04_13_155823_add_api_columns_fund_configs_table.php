<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddApiColumnsFundConfigsTable
 */
class AddApiColumnsFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('backoffice_enabled')->default(false)->after('limit_generator_amount');
            $table->boolean('backoffice_status')->default(false)->after('backoffice_enabled');
            $table->string('backoffice_url')->nullable()->after('backoffice_status');
            $table->string('backoffice_key')->nullable()->after('backoffice_url');
            $table->string('backoffice_certificate', 2000)->nullable()->after('backoffice_key');
            $table->boolean('backoffice_fallback')->default(true)->after('backoffice_certificate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn([
                'backoffice_enabled', 'backoffice_url', 'backoffice_key',
                'backoffice_certificate', 'backoffice_fallback',
            ]);
        });
    }
}
