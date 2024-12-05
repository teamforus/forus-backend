<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('record_type_id');
            $table->string('value', 400);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('profile_id')
                ->references('id')
                ->on('profiles')
                ->onDelete('restrict');

            $table->foreign('record_type_id')
                ->references('id')
                ->on('record_types')
                ->onDelete('restrict');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_records');
    }
};
