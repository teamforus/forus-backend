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
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('pre_check_enabled')->default(false)->after('digid_cgi_tls_cert');
            $table->string('pre_check_title', 50)->after('pre_check_enabled');
            $table->string('pre_check_homepage_title', 50)->after('pre_check_title');
            $table->string('pre_check_description', 1000)->after('pre_check_title');
            $table->string('pre_check_homepage_description', 1000)->after('pre_check_description');
            $table->string('pre_check_homepage_label', 50)->after('pre_check_homepage_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('pre_check_enabled');
            $table->dropColumn('pre_check_title');
            $table->dropColumn('pre_check_homepage_title');
            $table->dropColumn('pre_check_description');
            $table->dropColumn('pre_check_homepage_description');
            $table->dropColumn('pre_check_homepage_label');
        });
    }
};
