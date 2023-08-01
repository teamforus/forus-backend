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
        Schema::create('product_reservation_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_reservation_field_id');
            $table->unsignedBigInteger('product_reservation_id');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('organization_reservation_field_id', 'organization_field_id_foreign')
                ->references('id')->on('organization_reservation_fields')
                ->onDelete('cascade');

            $table->foreign('product_reservation_id')
                ->references('id')->on('product_reservations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reservation_field_values');
    }
};
