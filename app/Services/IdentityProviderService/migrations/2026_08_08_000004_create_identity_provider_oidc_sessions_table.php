<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('identity_provider_oidc_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('connection_id')->nullable();
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->unsignedInteger('implementation_id')->nullable();
            $table->unsignedInteger('identity_id')->nullable();
            $table->unsignedInteger('identity_proxy_id')->nullable();
            $table->string('mode', 32)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->char('state', 64)->unique();
            $table->text('nonce');
            $table->text('code_verifier');
            $table->text('authorization_url');
            $table->text('final_url');
            $table->string('target', 200)->nullable();
            $table->char('exchange_token_hash', 64)->nullable()->unique();
            $table->char('browser_token_hash', 64)->nullable();
            $table->text('verified_account')->nullable();
            $table->timestamp('exchange_expires_at')->nullable();
            $table->timestamp('exchange_consumed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('membership_id')
                ->references('id')->on('identity_provider_memberships')->restrictOnDelete();
            $table->foreign('implementation_id')->references('id')->on('implementations')->restrictOnDelete();
            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('identity_proxy_id')
                ->references('id')->on('identity_proxies')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_oidc_sessions');
    }
};
