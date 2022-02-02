<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBackofficeCertificateField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function(Blueprint $table) {
            $table->string('backoffice_certificate', 8000)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_configs', function(Blueprint $table) {
            $table->string('backoffice_certificate', 2000)->change();
        });
    }
}
