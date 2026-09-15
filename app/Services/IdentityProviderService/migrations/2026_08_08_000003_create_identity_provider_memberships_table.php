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
        Schema::create('identity_provider_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('connection_id');
            $table->unsignedBigInteger('external_identity_id')->index();
            $table->unsignedInteger('identity_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('claim_state', 32)->default('pending_link')->index();
            $table->string('consent_version', 32)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique(['connection_id', 'identity_id'], 'idp_memberships_identity_unique');
            $table->unique(['connection_id', 'employee_id'], 'idp_memberships_employee_unique');
            $table->unique(['connection_id', 'external_identity_id'], 'idp_memberships_connection_external_unique');
            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('external_identity_id')->references('id')->on('external_identities')->restrictOnDelete();
            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_memberships');
    }
};
