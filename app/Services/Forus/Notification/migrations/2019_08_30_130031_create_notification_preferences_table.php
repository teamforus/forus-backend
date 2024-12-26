<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('identity_address', 200);
            $table->string('mail_key', 30);
            $table->boolean('subscribed')->default(true);
            $table->timestamps();

            $table->index([
                'identity_address', 'mail_key',
            ]);

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
        Schema::dropIfExists('notification_preferences');
    }
};
