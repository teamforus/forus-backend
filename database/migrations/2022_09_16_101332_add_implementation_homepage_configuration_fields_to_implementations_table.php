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
    public function up()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('show_homepage_products')->default(true)->after('email_signature');
            $table->boolean('show_homepage_map')->default(true)->after('show_homepage_products');
            $table->boolean('show_providers_map')->default(true)->after('show_homepage_map');
            $table->boolean('show_provider_map')->default(true)->after('show_providers_map');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('show_homepage_products');
            $table->dropColumn('show_homepage_map');
            $table->dropColumn('show_providers_map');
            $table->dropColumn('show_provider_map');
        });
    }
};
