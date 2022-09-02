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
        DB::statement(
            "ALTER TABLE `announcements` CHANGE `scope` `scope` ".
            "ENUM('dashboards', 'sponsor', 'provider', 'validator', 'webshop') DEFAULT 'sponsor';"
        );

        Schema::table('announcements', function (Blueprint $table) {
            $table->unsignedInteger('implementation_id')->nullable()->after('active');

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `announcements` CHANGE `scope` `scope` ".
            "ENUM('dashboards', 'sponsor', 'provider', 'validator') DEFAULT 'sponsor';"
        );

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign('announcements_implementation_id_foreign');
            $table->dropColumn('implementation_id');
        });
    }
};