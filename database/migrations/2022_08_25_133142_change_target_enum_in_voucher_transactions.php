<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE voucher_transactions MODIFY COLUMN target ENUM('identity', 'provider', 'self')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE voucher_transactions MODIFY COLUMN target ENUM('identity', 'provider')");
    }
};
