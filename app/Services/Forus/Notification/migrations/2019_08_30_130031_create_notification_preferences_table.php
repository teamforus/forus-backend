<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('identity_address', 200);
            $table->unsignedSmallInteger('notification_type_id');
            $table->boolean('subscribed')->default(true);
            $table->timestamps();

            $table->foreign('identity_address')->references('address')->on('identities')->onDelete('cascade');
            $table->foreign('notification_type_id')->references('id')->on('notification_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_preferences');
    }
}
