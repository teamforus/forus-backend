<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateExpireAtToRegularVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $vouchers = \App\Models\Voucher::query()->whereNull('parent_id')->get();

        foreach ($vouchers as $voucher) {
            $voucher->update([
                'expire_at' => $voucher->fund->end_date
            ]);
        }
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
