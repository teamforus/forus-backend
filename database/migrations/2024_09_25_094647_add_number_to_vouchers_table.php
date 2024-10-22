<?php

use App\Models\Voucher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable()->after('id');
        });

        while (($list = DB::table('vouchers')->whereNull('number')->get())->isNotEmpty()) {
            $list->each(fn ($val) => DB::table('vouchers')->where('id', $val->id)->update([
                'number' => Voucher::makeUniqueNumber(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
