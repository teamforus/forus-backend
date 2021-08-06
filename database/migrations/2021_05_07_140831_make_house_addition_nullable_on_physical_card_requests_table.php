<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class MakeHouseAdditionNullableOnPhysicalCardRequestsTable
 * @noinspection PhpUnused
 */
class MakeHouseAdditionNullableOnPhysicalCardRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('physical_card_requests', function(Blueprint $table) {
            $table->string('house_addition', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
