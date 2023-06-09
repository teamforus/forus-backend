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
        Schema::create('identity_2fa', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('identity_address');
            $table->enum('state', ['pending', 'active', 'deactivated'])->default('pending');
            $table->unsignedBigInteger('auth_2fa_provider_id')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('secret', 100)->nullable();
            $table->string('secret_url', 800)->nullable();
            $table->string('code', 20);
            $table->string('deactivation_code', 20);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');

            $table->foreign('auth_2fa_provider_id')
                ->references('id')
                ->on('auth_2fa_providers')
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
        Schema::dropIfExists('identity_2fa');
    }
};
