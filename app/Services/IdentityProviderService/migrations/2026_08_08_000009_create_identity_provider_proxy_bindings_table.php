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
        Schema::create('identity_provider_proxy_bindings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('identity_proxy_id')->unique();
            $table->unsignedBigInteger('connection_id')->index();
            $table->unsignedBigInteger('membership_id')->index();
            $table->timestamps();

            $table->foreign('identity_proxy_id')->references('id')->on('identity_proxies')->cascadeOnDelete();
            $table->foreign('connection_id')
                ->references('id')->on('identity_provider_connections')->restrictOnDelete();
            $table->foreign('membership_id')
                ->references('id')->on('identity_provider_memberships')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_provider_proxy_bindings');
    }
};
