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
        Schema::create('openid_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('openid_flow_id')->index();
            $table->unsignedInteger('implementation_id')->index();
            $table->string('client_type', 50);
            $table->string('identity_address', 200)->nullable();
            $table->string('session_uid', 200)->unique();
            $table->string('session_final_url', 2000);
            $table->string('openid_auth_redirect_url', 2000);
            $table->string('session_request', 50)->default('auth');
            $table->string('session_state', 50)->default('pending')->index();
            $table->string('state', 200)->unique();
            $table->string('nonce', 200);
            $table->string('code_verifier', 200)->nullable();
            $table->string('target', 200)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('restrict');

            $table->foreign('openid_flow_id')
                ->references('id')
                ->on('openid_flows')
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
        Schema::dropIfExists('openid_sessions');
    }
};
