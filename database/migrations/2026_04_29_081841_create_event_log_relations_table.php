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
        Schema::create('event_log_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_log_id');
            $table->unsignedInteger('fund_id')->nullable();
            $table->timestamps();

            $table->unique('event_log_id');

            $table->foreign('event_log_id')
                ->references('id')
                ->on('event_logs')
                ->onDelete('cascade');

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_log_relations');
    }
};
