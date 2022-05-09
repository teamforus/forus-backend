<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Voucher;

/**
 * @noinspection PhpUnused
 */
class CreateVoucherTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('voucher_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('voucher_id')->unsigned();
            $table->string('address');
            $table->boolean('need_confirmation')->default(true);
            $table->timestamps();

            $table->foreign('voucher_id'
            )->references('id')->on('vouchers')->onDelete('cascade');
        });

        Voucher::get()->each(function (Voucher $voucher) {
            $voucher->tokens()->create(array_merge($voucher->only('address'), [
                'need_confirmation' => false,
            ]));

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
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('address', 200);
        });

        Voucher::get()->each(function (Voucher $voucher) {
            if ($voucherToken = $voucher->token_without_confirmation) {
                $voucher->update($voucherToken->only('address'));
            }
        });

        Schema::dropIfExists('voucher_tokens');
    }
}
