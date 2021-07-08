<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateProductReservationsTable
 * @noinspection PhpUnused
 */
class CreateProductReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('product_reservations', function (Blueprint $table) {
            $states = [
                'pending', 'accepted', 'rejected', 'canceled', 'complete',
            ];

            $priceTypes = [
                'free', 'regular', 'discount_percentage', 'discount_fixed',
            ];

            $table->bigIncrements('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('voucher_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('voucher_transaction_id')->nullable();
            $table->unsignedInteger('fund_provider_product_id')->nullable();
            $table->decimal('amount');
            $table->decimal('price');
            $table->decimal('price_discount');
            $table->string('code', 20);
            $table->enum('price_type', $priceTypes);
            $table->enum('state', $states)->default('pending');
            $table->string('note', 2000)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')
                ->on('employees')->onDelete('NO ACTION');

            $table->foreign('product_id')->references('id')
                ->on('products')->onDelete('NO ACTION');

            $table->foreign('voucher_id')->references('id')
                ->on('vouchers')->onDelete('NO ACTION');

            $table->foreign('voucher_transaction_id')->references('id')
                ->on('voucher_transactions')->onDelete('NO ACTION');

            $table->foreign('fund_provider_product_id')->references('id')
                ->on('fund_provider_products')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reservations');
    }
}
