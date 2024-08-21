<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->enum('type', ['email', 'push'])->default('email')->after('mail_key');

            $table->renameColumn('mail_key', 'key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function(Blueprint $table) {
            $table->dropColumn('type');

            $table->renameColumn('key', 'mail_key');
        });
    }
};