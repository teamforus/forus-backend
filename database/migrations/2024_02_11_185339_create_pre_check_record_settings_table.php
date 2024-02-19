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
        Schema::create('pre_check_record_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pre_check_record_id');
            $table->unsignedInteger('fund_id');
            $table->string('description', 1000)->nullable();
            $table->unsignedInteger('impact_level')->nullable();
            $table->boolean('is_knock_out')->default(false);
            $table->timestamps();

            $table->foreign('pre_check_record_id')
                ->references('id')->on('pre_check_records')
                ->onDelete('restrict');

            $table->foreign('fund_id')
                ->references('id')->on('funds')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_check_record_settings');
    }
};
