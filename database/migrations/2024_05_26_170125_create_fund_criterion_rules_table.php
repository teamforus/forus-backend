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
        Schema::create('fund_criterion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fund_criterion_id');
            $table->string('record_type_key');
            $table->enum('operator', ['>', '>=', '=', '!=', '<=', '<']);
            $table->string('value');
            $table->timestamps();

            $table->foreign('fund_criterion_id')
                ->references('id')
                ->on('fund_criteria')
                ->onDelete('restrict');

            $table->foreign('record_type_key')
                ->references('key')
                ->on('record_types')
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
        Schema::dropIfExists('fund_criterion_rules');
    }
};
