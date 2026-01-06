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
        Schema::create('prevalidation_request_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_type_key');
            $table->unsignedBigInteger('prevalidation_request_id');
            $table->string('value')->default('');
            $table->timestamps();

            $table->foreign('record_type_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('restrict');

            $table->foreign('prevalidation_request_id')
                ->references('id')
                ->on('prevalidation_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prevalidation_request_records');
    }
};
