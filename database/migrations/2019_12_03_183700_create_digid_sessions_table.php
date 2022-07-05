<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class CreateDigidSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('digid_sessions', function (Blueprint $table) {
            $states = [
                'created', 'pending_authorization', 'authorized', 'expired', 'error'
            ];

            $table->increments('id');
            $table->enum('state', $states)->default('created');
            $table->unsignedInteger('implementation_id')->nullable();
            $table->string('client_type', 20)->nullable();
            $table->string('identity_address', 200)->nullable();

            $table->string('session_uid', 200);
            $table->string('session_secret', 200);
            $table->string('session_final_url', 200);
            $table->string('session_request', 100);

            $table->string('digid_rid', 200)->nullable();
            $table->string('digid_uid', 200)->nullable();
            $table->string('digid_app_url', 500)->nullable();
            $table->string('digid_as_url', 500)->nullable();
            $table->string('digid_auth_redirect_url', 500)->nullable();
            $table->string('digid_error_code', 10)->nullable();
            $table->string('digid_error_message', 200)->nullable();

            $table->string('digid_request_aselect_server', 20)->nullable();
            $table->string('digid_response_aselect_server', 20)->nullable();
            $table->string('digid_response_aselect_credentials', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('digid_sessions');
    }
}
