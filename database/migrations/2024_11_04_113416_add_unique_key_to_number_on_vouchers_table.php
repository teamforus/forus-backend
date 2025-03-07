<?php

use App\Models\Voucher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unique('number');
        });

        while (($list = DB::table('vouchers')->whereNull('number')->get())->isNotEmpty()) {
            $list->each(function ($val) {
                /** @var Voucher|object $val */
                $number = Voucher::makeUniqueNumber();

                DB::table('vouchers')->where('id', $val->id)->update([ 'number' => $number ]);

                DB::table('event_logs')
                    ->where('loggable_type', 'voucher')
                    ->where('loggable_id', $val->id)
                    ->update([ 'data->voucher_number' => $number ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('vouchers_number_unique');
        });
    }
};
