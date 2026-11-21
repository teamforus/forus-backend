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
        Schema::create('identity_provider_tenant_reservations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('organization_id')->index();
            $table->unsignedBigInteger('connection_id')->index();
            $table->string('provider', 32);
            $table->uuid('tenant_id');
            $table->timestamp('first_connected_at');
            $table->timestamp('last_connected_at');
            $table->timestamps();

            $table->unique(['provider', 'tenant_id'], 'idp_tenant_reservations_provider_tenant_unique');
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_tenant_reservations');
    }
};
