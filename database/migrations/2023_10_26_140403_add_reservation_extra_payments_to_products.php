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
            $table->decimal('extra_amount')->after('amount')->default(0);
        });

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('waiting', 'pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client', 'canceled_payment_expired') DEFAULT 'pending';"
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
        });

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client') DEFAULT 'pending';"
        );
    }
};
