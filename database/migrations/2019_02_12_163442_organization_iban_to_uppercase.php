<?php

use Illuminate\Database\Migrations\Migration;

class OrganizationIbanToUppercase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("UPDATE organizations SET `iban` = UPPER(`iban`)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
