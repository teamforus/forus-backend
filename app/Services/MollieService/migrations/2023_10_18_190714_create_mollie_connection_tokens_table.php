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
        Schema::create('mollie_connection_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('remember_token');
            $table->timestamp('expired_at');
            $table->softDeletes();
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
        Schema::dropIfExists('mollie_connection_tokens');
    }
};
