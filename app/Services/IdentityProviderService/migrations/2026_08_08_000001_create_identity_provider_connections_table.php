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
        Schema::create('identity_provider_connections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->unsignedInteger('organization_id')->index();
            $table->string('provider', 32);
            $table->uuid('tenant_id');
            $table->string('issuer', 500);
            $table->enum('status', ['enabled', 'paused', 'disconnected'])->index();

            $table->unsignedInteger('current_organization_id')
                ->nullable()->storedAs("CASE WHEN status != 'disconnected' THEN organization_id ELSE NULL END")->unique();
            $table->uuid('current_tenant_id')
                ->nullable()->storedAs("CASE WHEN status != 'disconnected' THEN tenant_id ELSE NULL END");
            $table->unsignedInteger('created_by_identity_id');
            $table->unsignedInteger('updated_by_identity_id');
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_auth_success_at')->nullable();
            $table->timestamp('last_auth_failure_at')->nullable();
            $table->string('last_auth_failure_code', 100)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'current_tenant_id'], 'idp_connections_current_tenant_unique');
            $table->index(['provider', 'tenant_id'], 'idp_connections_provider_tenant_index');
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('created_by_identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('updated_by_identity_id')->references('id')->on('identities')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_connections');
    }
};
