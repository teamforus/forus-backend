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
        Schema::create('fund_request_missed_records', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['info', 'warning'])->default('info');
            $table->string('group');
            $table->string('field');
            $table->unsignedInteger('fund_request_id');
            $table->timestamps();

            $table->foreign('fund_request_id')
                ->references('id')
                ->on('fund_requests')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_request_missed_records');
    }
};
