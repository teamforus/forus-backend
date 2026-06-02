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
        Schema::create('prevalidation_request_missed_records', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['info', 'warning'])->default('info');
            $table->string('group');
            $table->string('field');
            $table->unsignedBigInteger('prevalidation_request_id');
            $table->timestamps();

            $table->foreign('prevalidation_request_id', 'prevalidation_missed_records_request_id_foreign')
                ->references('id')
                ->on('prevalidation_requests')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prevalidation_request_missed_records');
    }
};
