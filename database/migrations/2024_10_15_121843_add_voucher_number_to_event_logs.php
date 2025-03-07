<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $vouchers = DB::table('vouchers')->get();

            foreach ($vouchers as $voucher) {
                DB::table('event_logs')
                    ->where('loggable_type', 'voucher')
                    ->where('loggable_id', $voucher->id)
                    ->update([
                        'data->voucher_number' => $voucher->number,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
