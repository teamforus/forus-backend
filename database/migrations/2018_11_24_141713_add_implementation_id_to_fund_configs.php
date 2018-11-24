<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImplementationIdToFundConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->integer('implementation_id')->unsigned()->nullable()
                ->index()->after('fund_id');

            $table->foreign('implementation_id'
            )->references('id')->on('implementations')->onDelete('cascade');
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
            $table->dropColumn('implementation_id');
        });
    }
}
