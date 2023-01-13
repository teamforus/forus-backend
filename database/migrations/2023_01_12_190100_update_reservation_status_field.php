<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ProductReservation;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client', 'complete') DEFAULT 'pending';"
        );

        ProductReservation::query()->onlyTrashed()->each(function (ProductReservation $reservation) {
            $reservation->updateModel([
                'state'         => ProductReservation::STATE_CANCELED_BY_CLIENT,
                'canceled_at'   => $reservation->deleted_at
            ])->restore();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'complete') DEFAULT 'pending';"
        );
    }
};
