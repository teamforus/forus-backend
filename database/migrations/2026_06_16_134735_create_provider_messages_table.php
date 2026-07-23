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
        Schema::create('provider_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('identity_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->morphs('mailable');
            $table->string('type');
            $table->text('message');
            $table->timestamps();

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->cascadeOnDelete();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_messages');
    }
};
