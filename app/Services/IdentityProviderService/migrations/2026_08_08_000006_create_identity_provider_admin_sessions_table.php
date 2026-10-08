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
        Schema::create('identity_provider_admin_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('organization_id')->index();
            $table->unsignedBigInteger('connection_id')->nullable();
            $table->unsignedBigInteger('previous_connection_id')->nullable();
            $table->unsignedInteger('requested_by_identity_id');
            $table->text('final_url');
            $table->string('status', 32)->index();
            $table->char('oidc_state_hash', 64)->unique();
            $table->text('oidc_nonce');
            $table->text('oidc_code_verifier');
            $table->text('oidc_authorization_url');
            $table->char('consent_state_hash', 64)->nullable()->unique();
            $table->uuid('expected_tenant_id')->nullable();
            $table->uuid('admin_object_id')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('tenant_verified_at')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('previous_connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('requested_by_identity_id', 'idp_admin_sessions_requested_by_identity_foreign')
                ->references('id')->on('identities')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_admin_sessions');
    }
};
