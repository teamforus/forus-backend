<?php

use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Builder;
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
        Schema::table('product_reservations', function (Blueprint $table) {
            $table->enum('state', ['pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client', 'complete'])
                ->default('pending')
                ->change();
        });

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

        Schema::table('product_reservations', function (Blueprint $table) {
            $table->enum('state', ['pending', 'accepted', 'rejected', 'canceled', 'complete'])
                ->default('pending')
                ->change();
        });
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
};
