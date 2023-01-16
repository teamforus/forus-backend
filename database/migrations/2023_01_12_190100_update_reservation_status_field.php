<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Builder;
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

        $reservations = $this->getPendingReservationsQuery()->onlyTrashed()->where([
            'state' => 'pending',
        ])->get();

        foreach ($reservations as $reservation) {
            $reservation->update([
                'state' => 'canceled_by_client',
                'canceled_at' => $reservation->deleted_at,
            ]);

            $reservation->restore();
        }
    }

    /**
     * @return Builder|ProductReservation
     */
    protected function getPendingReservationsQuery(): Builder|ProductReservation
    {
        return ProductReservation::query()
            ->whereNull('employee_id')
            ->whereNull('voucher_transaction_id')
            ->whereDoesntHave('product_voucher');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $reservations = $this->getPendingReservationsQuery()->where([
            'state' => 'canceled_by_client',
        ])->get();

        foreach ($reservations as $reservation) {
            $reservation->forceFill([
                'state' => 'pending',
                'deleted_at' => $reservation->canceled_at ?: now(),
            ])->update();
        }

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'complete') DEFAULT 'pending';"
        );
    }
};
