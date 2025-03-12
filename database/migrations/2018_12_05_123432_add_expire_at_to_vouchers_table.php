<?php

use App\Models\Voucher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->timestamp('expire_at')->nullable();
        });

        $vouchers = Voucher::has('product')->has('fund')->get();

        foreach ($vouchers as $voucher) {
            $voucher->update([
                'expire_at' => $voucher->fund->end_date->gt(
                    $voucher->product->expire_at
                ) ? $voucher->product->expire_at : $voucher->fund->end_date,
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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('expire_at');
        });
    }
};
