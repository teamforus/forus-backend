<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->string('sponsor_api_error_action')->nullable()->after('limit_generator_amount');
            $table->string('sponsor_api_url')->nullable()->after('limit_generator_amount');
            $table->string('sponsor_api_token')->nullable()->after('limit_generator_amount');
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
            $table->dropColumn(['sponsor_api_url', 'sponsor_api_token', 'sponsor_api_error_action']);
        });
    }
}
