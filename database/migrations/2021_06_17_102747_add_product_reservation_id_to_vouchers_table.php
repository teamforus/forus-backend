<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddProductReservationIdToVouchersTable
 * @noinspection PhpUnused
 */
class AddProductReservationIdToVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('product_reservation_id')->nullable()->after('returnable');

            $table->foreign('product_reservation_id')->references('id')
                ->on('product_reservations')->onDelete('NO ACTION');
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
            $table->dropForeign('vouchers_product_reservation_id_foreign');
            $table->dropColumn('product_reservation_id');
        });
    }
}
