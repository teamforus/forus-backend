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
        Schema::create('fund_request_record_group_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_request_record_group_id')->nullable();
            $table->string('record_type_key');

            $table->timestamps();

            $table
                ->foreign('fund_request_record_group_id', 'group_records_record_group_id_foreign')
                ->references('id')
                ->on('fund_request_record_groups')
                ->onDelete('cascade');

            $table->foreign('record_type_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_request_record_group_records');
    }
};
