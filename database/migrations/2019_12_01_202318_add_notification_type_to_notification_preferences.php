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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->dropColumn('type');

            $table->renameColumn('key', 'mail_key');
        });
    }
}