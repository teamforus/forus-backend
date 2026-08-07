<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fund_product_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fund_id');
            $table->enum('type', ['all_except_selected', 'only_selected']);
            $table->enum('state', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('limit');
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_product_limits');
    }
};
