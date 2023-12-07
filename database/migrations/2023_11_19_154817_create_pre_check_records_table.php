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
        Schema::create('pre_check_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('record_type_key', 180);
            $table->unsignedInteger('pre_check_id')->nullable();
            $table->unsignedInteger('implementation_id');
            $table->unsignedInteger('order')->nullable();
            $table->string('title', 200);
            $table->string('title_short', 100);
            $table->string('description', 2000)->nullable();
            $table->timestamps();

            $table->foreign('pre_check_id')
                ->references('id')
                ->on('pre_checks')
                ->onDelete('set null');

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
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
        Schema::dropIfExists('pre_check_records');
    }
};
