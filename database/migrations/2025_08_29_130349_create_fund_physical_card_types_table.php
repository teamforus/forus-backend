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
        Schema::create('fund_physical_card_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fund_id');
            $table->unsignedBigInteger('physical_card_type_id');
            $table->boolean('allow_physical_card_linking')->default(false);
            $table->boolean('allow_physical_card_requests')->default(false);
            $table->boolean('allow_physical_card_deactivation')->default(false);
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');

            $table->foreign('physical_card_type_id')
                ->references('id')
                ->on('physical_card_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_physical_card_types');
    }
};
