<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Voucher;

class CreateVoucherTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('voucher_id')->unsigned();
            $table->string('address');
            $table->boolean('need_confirmation', true);
            $table->timestamps();

            $table->foreign('voucher_id'
            )->references('id')->on('vouchers')->onDelete('cascade');
        });

        Voucher::all()->each(function ($voucher) {
            /** @var Voucher $voucher */
            $voucher->tokens()->create([
                'address'           => $voucher->address,
                'need_confirmation' => false,
            ]);

            $voucher->tokens()->create([
                'address'           => resolve('token_generator')->address(),
                'need_confirmation' => true,
            ]);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('address', 200);
        });

        Voucher::all()->each(function ($voucher) {
            /** @var Voucher $voucher */
            if ($voucherToken = $voucher->tokens()->where(
                'need_confirmation', false
            )->first()) {
                $voucher->update(['address' => $voucherToken->address]);
            }
        });

        Schema::dropIfExists('voucher_tokens');
    }
}
