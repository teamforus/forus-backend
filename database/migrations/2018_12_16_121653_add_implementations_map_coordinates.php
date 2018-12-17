<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImplementationsMapCoordinates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('implementations', function(Blueprint $table) {
            $table->double('lon')->nullable()->after('url_app');
            $table->double('lat')->nullable()->after('lon');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function(Blueprint $table) {
            $table->dropColumn('lon');
            $table->dropColumn('lat');
        });
    }
}
