<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\EventLogService\Models\EventLog;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        EventLog::whereLoggableType('App\Models\ProductReservation')->update([
            'loggable_type' => 'product_reservation',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        EventLog::whereLoggableType('product_reservation')->update([
            'loggable_type' => 'App\Models\ProductReservation',
        ]);
    }
};
