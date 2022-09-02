<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class CreateSessionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('session_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('session_id')->unsigned();
            $table->string('ip', 46);
            $table->string('client_type', 50)->nullable();
            $table->string('client_version', 10)->nullable();
            $table->string('endpoint', 200)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->timestamps();

            $table->foreign('session_id')
                ->references('id')->on('sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('session_requests');
    }
}
