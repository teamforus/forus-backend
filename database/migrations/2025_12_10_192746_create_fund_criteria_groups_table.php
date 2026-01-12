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
        Schema::create('fund_criteria_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('fund_id');
            $table->smallInteger('order');
            $table->boolean('required')->default(false);
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');
        });

        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->foreignId('fund_criteria_group_id')->after('fund_criteria_step_id')->nullable();
            $table->enum('fill_type', ['manual', 'prefill'])->default('manual')->after('fund_criteria_group_id');

            $table->foreign('fund_criteria_group_id')
                ->references('id')
                ->on('fund_criteria_groups')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->dropForeign(['fund_criteria_group_id']);
            $table->dropColumn('fund_criteria_group_id', 'fill_type');
        });

        Schema::dropIfExists('fund_criteria_groups');
    }
};
