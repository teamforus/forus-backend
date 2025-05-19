<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('email_log_attachments')
            ->whereIn(
                'email_log_id',
                DB::table('email_logs')
                    ->select('id')
                    ->where('system_notification_key', 'notifications_fund_providers.revoked_budget')
            )
            ->delete();

        DB::table('email_logs')
            ->where('system_notification_key', 'notifications_fund_providers.revoked_budget')
            ->delete();

        DB::table('notification_templates')
            ->whereIn(
                'system_notification_id',
                DB::table('system_notifications')
                    ->select('id')
                    ->where('key', 'notifications_fund_providers.revoked_budget')
            )
            ->where('type', 'mail')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
