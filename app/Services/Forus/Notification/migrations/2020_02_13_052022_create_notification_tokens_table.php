<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class CreateNotificationTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('identity_address', 200);
            $table->enum('type', [
                'fcm', 'apn'
            ]);
            $table->string('token', 200);
            $table->timestamps();

            $table->foreign('identity_address')->references('address')
                ->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_tokens');
    }
}
