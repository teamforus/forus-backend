<?php

use App\Models\Voucher;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        foreach (Voucher::whereNotNull('expire_at')->get() as $voucher) {
            $voucher->update([
                'expire_at' => $voucher->expire_at->subDay(),
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
        foreach (Voucher::whereNotNull('expire_at')->get() as $voucher) {
            $voucher->update([
                'expire_at' => $voucher->expire_at->addDay(),
            ]);
        }
    }
};
