<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('identity_provider_scim_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('connection_id');
            $table->char('token_hash', 64)->unique();
            $table->unsignedInteger('created_by_identity_id');
            $table->unsignedInteger('revoked_by_identity_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['connection_id', 'revoked_at'], 'idp_scim_credentials_connection_revoked_index');
            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('created_by_identity_id', 'idp_scim_credentials_created_by_identity_foreign')
                ->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('revoked_by_identity_id', 'idp_scim_credentials_revoked_by_identity_foreign')
                ->references('id')->on('identities')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_scim_credentials');
    }
};
