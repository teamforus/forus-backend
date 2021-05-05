<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeactivatedStateToVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE vouchers MODIFY COLUMN state ENUM('pending', 'active', 'deactivated') NOT NULL DEFAULT 'active'");
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('deactivation_reason')->after('state')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE vouchers MODIFY COLUMN state ENUM('pending', 'active') NOT NULL DEFAULT 'active'");
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('deactivation_reason');
        });
    }
}
