<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemNotification;

class SystemNotificationsTableSeeder extends Seeder
{
    protected array $systemNotifications = [
        "notifications_identities.added_employee" => ["database", "mail", "push"],
        "notifications_identities.changed_employee_roles" => ["database"],
        "notifications_identities.removed_employee" => ["database", "push"],

        "notifications_fund_providers.approved_budget" => ["database", "mail", "push"],
        "notifications_fund_providers.approved_products" => [], // TODO can database notifications be removed

        "notifications_fund_providers.revoked_budget" => ["database", "mail"],
        "notifications_fund_providers.revoked_products" => [], // TODO can database notifications be removed
        "notifications_fund_providers.sponsor_message" => ["database"],

        "notifications_fund_providers.state_accepted" => ["database", "mail"],
        "notifications_fund_providers.state_rejected" => ["database", "mail"],

        "notifications_identities.requester_provider_approved_budget" => ["database"],
        "notifications_identities.requester_provider_approved_products" => [],// TODO can database notifications be removed
        "notifications_identities.requester_sponsor_custom_notification" => ["mail"],

        "notifications_fund_requests.created_validator_employee" => ["database"],

        "notifications_identities.fund_request_created" => ["database", "mail"],
        "notifications_identities.fund_request_denied" => ["database", "mail"],
        "notifications_identities.fund_request_approved" => ["database", "mail"],
        "notifications_identities.fund_request_disregarded" => ["database", "mail"],
        "notifications_identities.fund_request_record_declined" => ["database", "mail"],
        "notifications_identities.fund_request_feedback_requested" => ["database", "mail"],

        "notifications_identities.reimbursement_submitted" => ["database", "mail"],
        "notifications_identities.reimbursement_approved" => ["database", "mail"],
        "notifications_identities.reimbursement_declined" => ["database", "mail"],

        'notifications_fund_providers.fund_started' => ["database"],
        'notifications_fund_providers.fund_ended' => [], // TODO can database notifications be removed
        'notifications_fund_providers.fund_expiring' => [], // TODO can database notifications be removed

        "notifications_funds.created" => ["database"],
        "notifications_funds.started" => ["database"],
        "notifications_funds.ended" => [], // TODO can database notifications be removed
        "notifications_funds.expiring" => [], // TODO can database notifications be removed
        "notifications_funds.product_added" => ["database"],
        "notifications_funds.provider_applied" => ["database", "mail"],
        "notifications_funds.provider_message" => ["database"],
        "notifications_funds.product_subsidy_removed" => ["database"],
        "notifications_funds.balance_low" => ["database", "mail"],
        "notifications_funds.balance_supplied" => ["database"],

        'notifications_identities.requester_product_added' => [], // TODO can database notifications be removed
        'notifications_identities.requester_product_approved' => [], // TODO can database notifications be removed
        'notifications_identities.requester_product_revoked' => [], // TODO can database notifications be removed

        "notifications_identities.product_reservation_created" => ["database"],
        "notifications_identities.product_reservation_accepted" => ["database"],
        "notifications_identities.product_reservation_canceled" => ["database"],
        "notifications_identities.product_reservation_rejected" => ["database"],

        "notifications_products.approved" => ["database"],
        "notifications_products.expired" => ["database"],
        "notifications_products.reserved" => ["database", "mail"],
        "notifications_products.revoked" => ["database"],
        "notifications_products.sold_out" => ["database", "mail"],
        "notifications_products.reservation_canceled" => ["database"],

        "notifications_identities.product_voucher_shared" => ["database", "mail"],
        "notifications_identities.identity_voucher_assigned_budget" => ["database", "mail", "push"],
        "notifications_identities.identity_voucher_assigned_subsidy" => ["database", "mail", "push"],
        "notifications_identities.identity_voucher_assigned_product" => ["database", "mail", "push"],
        "notifications_identities.product_voucher_added" => ["database"],
        "notifications_identities.product_voucher_reserved" => ["database", "mail"],
        "notifications_identities.voucher_added_subsidy" => ["database"],
        "notifications_identities.voucher_added_budget" => ["database"],
        "notifications_identities.voucher_deactivated" => ["mail"],
        "notifications_identities.budget_voucher_expired" => [], // TODO can database notifications be removed
        "notifications_identities.product_voucher_expired" => ["database"],
        "notifications_identities.voucher_expire_soon_budget" => ["database", "mail"],
        "notifications_identities.voucher_expire_soon_product" => [], // TODO can database notifications be removed
        "notifications_identities.voucher_physical_card_requested" => ["database", "mail"],
        "notifications_identities.voucher_shared_by_email" => ["database"],
        "notifications_identities.voucher_budget_transaction" => ["database", "mail", "push"],
        "notifications_identities.voucher_subsidy_transaction" => ["database", "mail", "push"],
        "notifications_identities.product_voucher_transaction" => ["database", "push"],

        "notifications_fund_providers.bunq_transaction_success" => ["database"],

        "notifications_bank_connections.activated" => ["database"],
        "notifications_bank_connections.disabled_invalid" => ["database"],
        "notifications_bank_connections.monetary_account_changed" => ["database"],
        "notifications_bank_connections.expiring" => ["database", "mail"],

        "notifications_physical_card_requests.physical_card_request_created" => [], // TODO can database notifications be removed
    ];

    protected array $optionalNotifications = [
        'notifications_fund_providers.approved_budget',
        'notifications_fund_providers.revoked_budget',
        'notifications_fund_providers.state_accepted',
        'notifications_fund_providers.state_rejected',
        'notifications_identities.identity_voucher_assigned_budget',
        'notifications_identities.identity_voucher_assigned_subsidy',
        'notifications_identities.identity_voucher_assigned_product',
        'notifications_identities.product_voucher_reserved',
        'notifications_identities.voucher_expire_soon_budget',
    ];

    protected array $visibleNotifications = [
        'notifications_fund_providers.approved_budget',
        'notifications_fund_providers.revoked_budget',
        'notifications_fund_providers.sponsor_message',
        'notifications_fund_providers.state_accepted',
        'notifications_fund_providers.state_rejected',

        'notifications_identities.requester_provider_approved_budget',

        'notifications_identities.fund_request_created',
        'notifications_identities.fund_request_denied',
        'notifications_identities.fund_request_approved',
        "notifications_identities.fund_request_disregarded",
        'notifications_identities.fund_request_feedback_requested',

        'notifications_identities.reimbursement_submitted',
        'notifications_identities.reimbursement_approved',
        'notifications_identities.reimbursement_declined',

        'notifications_identities.product_reservation_created',
        'notifications_identities.product_reservation_accepted',
        'notifications_identities.product_reservation_canceled',
        'notifications_identities.product_reservation_rejected',

        'notifications_products.approved',
        'notifications_products.expired',
        'notifications_products.reserved',
        'notifications_products.revoked',
        'notifications_products.sold_out',
        'notifications_products.reservation_canceled',

        'notifications_identities.identity_voucher_assigned_budget',
        'notifications_identities.identity_voucher_assigned_subsidy',
        'notifications_identities.identity_voucher_assigned_product',

        'notifications_identities.voucher_added_budget',
        'notifications_identities.voucher_added_subsidy',
        'notifications_identities.product_voucher_added',
        'notifications_identities.product_voucher_reserved',
        'notifications_identities.voucher_deactivated',
        'notifications_identities.product_voucher_expired',
        'notifications_identities.voucher_expire_soon_budget',
        'notifications_identities.voucher_physical_card_requested',

        'notifications_identities.product_voucher_transaction',
        'notifications_identities.voucher_budget_transaction',
        'notifications_identities.voucher_subsidy_transaction',

        'notifications_fund_providers.bunq_transaction_success',
    ];

    protected array $editableNotifications = [
        'notifications_fund_providers.approved_budget',
        'notifications_fund_providers.revoked_budget',
        'notifications_fund_providers.state_accepted',
        'notifications_fund_providers.state_rejected',

        'notifications_identities.fund_request_created',
        'notifications_identities.fund_request_approved',
        'notifications_identities.fund_request_feedback_requested',

        'notifications_identities.reimbursement_submitted',
        'notifications_identities.reimbursement_approved',
        'notifications_identities.reimbursement_declined',

        'notifications_identities.identity_voucher_assigned_budget',
        'notifications_identities.identity_voucher_assigned_subsidy',
        'notifications_identities.identity_voucher_assigned_product',

        'notifications_identities.voucher_deactivated',
        'notifications_identities.voucher_expire_soon_budget',
        'notifications_identities.voucher_expire_soon_product',
        'notifications_identities.voucher_physical_card_requested',
    ];

    protected array $notificationGroups = [
        "requester_fund_request" => [
            "notifications_identities.fund_request_created",
            "notifications_identities.fund_request_approved",
            "notifications_identities.fund_request_denied",
            "notifications_identities.fund_request_disregarded",
            "notifications_identities.fund_request_record_declined",
            "notifications_identities.fund_request_feedback_requested"
        ],
        "requester_reimbursements" => [
            'notifications_identities.reimbursement_submitted',
            'notifications_identities.reimbursement_approved',
            'notifications_identities.reimbursement_declined',
        ],
        "requester_vouchers" => [
            "notifications_identities.identity_voucher_assigned_budget",
            "notifications_identities.voucher_added_budget",
            "notifications_identities.identity_voucher_assigned_subsidy",
            "notifications_identities.voucher_added_subsidy",
            "notifications_identities.identity_voucher_assigned_product",
            "notifications_identities.requester_product_revoked",
            "notifications_identities.requester_product_approved",
            "notifications_identities.requester_product_added",
            "notifications_identities.voucher_deactivated",
            "notifications_identities.voucher_expire_soon_budget",
            "notifications_identities.budget_voucher_expired",
            "notifications_identities.voucher_expire_soon_product",
            "notifications_identities.product_voucher_expired",
            "notifications_identities.voucher_physical_card_requested"
        ],
        "requester_transactions" => [
            "notifications_identities.requester_provider_approved_products",
            "notifications_identities.requester_provider_approved_budget",
            "notifications_identities.product_voucher_added",
            "notifications_identities.product_voucher_reserved",
            "notifications_identities.product_reservation_created",
            "notifications_identities.product_reservation_accepted",
            "notifications_identities.product_voucher_shared",
            "notifications_identities.product_reservation_rejected",
            "notifications_identities.product_reservation_canceled",
            "notifications_identities.voucher_shared_by_email",
            "notifications_identities.voucher_budget_transaction",
            "notifications_identities.voucher_subsidy_transaction",
            "notifications_identities.product_voucher_transaction"
        ],
        "provider_fund_requests" => [
            "notifications_fund_providers.approved_budget",
            "notifications_fund_providers.approved_products",
            'notifications_fund_providers.state_accepted',
            'notifications_fund_providers.state_rejected',
            "notifications_products.approved",
            'notifications_products.reservation_canceled',
            "notifications_fund_providers.revoked_budget",
            "notifications_fund_providers.revoked_products",
            "notifications_products.revoked",
            "notifications_fund_providers.sponsor_message"
        ],
        "provider_voucher_and_transactions" => [
            "notifications_fund_providers.bunq_transaction_success",
            "notifications_products.reserved",
            "notifications_products.expired",
            "notifications_products.sold_out",
            "notifications_fund_providers.fund_started",
            "notifications_fund_providers.fund_ended",
            "notifications_fund_providers.fund_expiring"
        ],
        "sponsor" => [
            "notifications_funds.product_subsidy_removed",
            "notifications_funds.balance_supplied",
            "notifications_funds.balance_low",
            "notifications_funds.provider_message",
            "notifications_funds.provider_applied",
            "notifications_funds.product_added",
            "notifications_fund_requests.created_validator_employee",
            "notifications_funds.created",
            "notifications_funds.started",
            "notifications_funds.ended",
            "notifications_funds.expiring"
        ],
        "other" => [
            "notifications_identities.added_employee",
            "notifications_identities.changed_employee_roles",
            "notifications_identities.removed_employee"
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->systemNotifications as $key => $notificationChannels) {
            $group = array_reduce(array_keys($this->notificationGroups), function($value, $groupKey) use ($key) {
                return $value ?: (in_array($key, $this->notificationGroups[$groupKey]) ? $groupKey : null);
            }, null);

            SystemNotification::updateOrCreate(compact('key'), [
                'push'      => in_array('push', $notificationChannels),
                'mail'      => in_array('mail', $notificationChannels),
                'database'  => in_array('database', $notificationChannels),
                'optional'  => in_array($key, $this->optionalNotifications),
                'visible'   => in_array($key, $this->visibleNotifications),
                'editable'  => in_array($key, $this->editableNotifications),
                'group'     => is_null($group) ? 'other' : $group,
                'order'     => is_null($group) ? 100 : array_search($key, $this->notificationGroups[$group]),
            ]);
        }
    }
}
