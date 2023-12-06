<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('reservation_extra_payments', ['global', 'yes', 'no'])
                ->default('global')
                ->after('reservation_birth_date');
        });

        Schema::table('product_reservations', function (Blueprint $table) {
            $table->decimal('amount_extra')->after('amount')->default(0);
        });

        $states = [
            'waiting', 'pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client',
            'canceled_payment_failed', 'canceled_payment_expired', 'canceled_payment_canceled',
        ];

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` " .
            "ENUM('" . implode("', '", $states) . "') DEFAULT 'pending';",
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reservation_extra_payments');
            $table->string('reservation_extra_payments', 50)->change();
        });
    }
};