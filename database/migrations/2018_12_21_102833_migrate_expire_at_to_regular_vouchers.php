<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        //
    }
};
