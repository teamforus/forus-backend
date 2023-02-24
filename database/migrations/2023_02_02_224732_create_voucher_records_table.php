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
        Schema::create('voucher_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('voucher_id');
            $table->unsignedInteger('record_type_id');
            $table->string('value', 2000);
            $table->text('note')->default('')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('voucher_id')
                ->references('id')
                ->on('vouchers')
                ->onDelete('cascade');

            $table->foreign('record_type_id')
                ->references('id')
                ->on('record_types')
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
        Schema::dropIfExists('voucher_records');
    }
};
