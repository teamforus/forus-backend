<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotificationTypeToNotificationPreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->enum('type', ['email', 'push'])->default(
                'email'
            )->after('mail_key');

            $table->renameColumn('mail_key', 'key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->dropColumn('type');

            $table->renameColumn('key', 'mail_key');
        });
    }
}
