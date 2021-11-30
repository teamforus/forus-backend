<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Notification;
use App\Models\FundRequest;
use App\Services\EventLogService\Models\EventLog;

class MigrateNotificationKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Notification::where([
            'data->key' => 'notifications_identities.voucher_transaction',
        ])->update([
            'data->key' => 'notifications_identities.voucher_budget_transaction',
        ]);

        $this->migrateFundRequests();
    }

    /**
     * Migrate fund_request_resolved to `approved` or `denied` notifications
     *
     * @return void
     */
    protected function migrateFundRequests(): void
    {
        $notifications = Notification::where([
            'data->key' => 'notifications_identities.fund_request_resolved',
        ])->get();

        foreach ($notifications as $notification) {
            if ($event = EventLog::find($notification->data['event_id'] ?? null)) {
                $approved = ($event->data['fund_request_state'] ?? null) == FundRequest::STATE_APPROVED;
                $state = ($approved ? 'approved' : 'denied');

                $notification->update([
                    'data->key' => 'notifications_identities.fund_request_' . $state,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Notification::where([
            'data->key' => 'notifications_identities.voucher_budget_transaction',
        ])->update([
            'data->key' => 'notifications_identities.voucher_transaction',
        ]);

        Notification::whereIn('data->key', [
            'notifications_identities.fund_request_denied',
            'notifications_identities.fund_request_approved',
        ])->update([
            'data->key' => 'notifications_identities.fund_request_resolved',
        ]);
    }
}
