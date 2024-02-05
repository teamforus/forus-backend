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
        Schema::create('mollie_connection_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('website');
            $table->string('mollie_id')->nullable();
            $table->enum('state', ['pending', 'active'])->default('pending');
            $table->boolean('current')->default(0);
            $table->unsignedBigInteger('mollie_connection_id');
            $table->timestamps();

            $table->foreign('mollie_connection_id')
                ->references('id')->on('mollie_connections')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mollie_connection_profiles');
    }
};
