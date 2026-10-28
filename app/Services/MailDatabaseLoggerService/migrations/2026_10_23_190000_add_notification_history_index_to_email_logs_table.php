<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->index(
                ['system_notification_key', 'event_log_id', 'created_at'],
                'email_logs_notification_event_created_index',
            );
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex('email_logs_notification_event_created_index');
        });
    }
};
