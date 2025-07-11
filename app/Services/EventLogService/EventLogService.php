<?php

namespace App\Services\EventLogService;

use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundPeriod;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\FundTopUpTransaction;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardRequest;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\ReservationExtraPayment;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use App\Services\BankService\Models\Bank;
use App\Services\BIConnectionService\Models\BIConnection;
use App\Services\EventLogService\Interfaces\IEventLogService;
use App\Services\MollieService\Models\MollieConnection;

class EventLogService implements IEventLogService
{
    /**
     * @param string $type
     * @param $model
     * @return array
     */
    public function modelToMeta(string $type, $model): array
    {
        $modelMeta = [
            'fund' => fn () => $this->fundMeta($model),
            'fund_config' => fn () => $this->fundConfigMeta($model),
            'fund_period' => fn () => $this->fundPeriodMeta($model),
            'fund_request' => fn () => $this->fundRequestMeta($model),
            'fund_request_record' => fn () => $this->fundRequestRecordMeta($model),
            'fund_request_clarification' => fn () => $this->fundRequestClarificationMeta($model),
            'fund_top_up_transaction' => fn () => $this->fundTopUpTransactionMeta($model),
            'sponsor' => fn () => $this->sponsorMeta($model),
            'provider' => fn () => $this->providerMeta($model),
            'product' => fn () => $this->productMeta($model),
            'voucher' => fn () => $this->voucherMeta($model),
            'organization' => fn () => $this->organizationMeta($model),
            'employee' => fn () => $this->employeeMeta($model),
            'product_reservation' => fn () => $this->productReservationMeta($model),
            'voucher_transaction' => fn () => $this->voucherTransactionMeta($model),
            'voucher_transaction_bulk' => fn () => $this->voucherTransactionBulkMeta($model),
            'voucher_record' => fn () => $this->voucherRecordMeta($model),
            'physical_card' => fn () => $this->physicalCardMeta($model),
            'physical_card_request' => fn () => $this->physicalCardRequestMeta($model),
            'bank' => fn () => $this->bankMeta($model),
            'bank_connection' => fn () => $this->bankConnectionMeta($model),
            'bank_connection_account' => fn () => $this->bankConnectionAccountMeta($model),
            'implementation' => fn () => $this->implementationMeta($model),
            'reimbursement' => fn () => $this->reimbursementMeta($model),
            'mollie_connection' => fn () => $this->mollieConnectionMeta($model),
            'reservation_extra_payment' => fn () => $this->reservationExtraPaymentMeta($model),
            'bi_connection' => fn () => $this->biConnectionMeta($model),
        ];

        return $modelMeta[$type] ? $modelMeta[$type]() : [];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundMeta(Fund $fund): array
    {
        return $this->keyPrepend([
            'id' => $fund->id,
            'name' => $fund->name,
            'external' => $fund->external,
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->start_date->clone()->addDay()->format('Y-m-d'),
            'end_date_minus1' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date->clone()->addDay()),
            'end_date_minus1_locale' => format_date_locale($fund->end_date),
            'amount_presets' => $fund->amount_presets?->map(fn ($preset) => $preset->only([
                'id', 'name', 'amount',
            ]))->toArray(),
        ], 'fund_');
    }

    /**
     * @param FundConfig $fundConfig
     * @return array
     */
    protected function fundConfigMeta(FundConfig $fundConfig): array
    {
        return $this->keyPrepend([
            'id' => $fundConfig->id,
            'email_required' => $fundConfig->email_required,
            'contact_info_enabled' => $fundConfig->contact_info_enabled,
            'contact_info_required' => $fundConfig->contact_info_required,
            'contact_info_message_custom' => $fundConfig->contact_info_message_custom,
            'contact_info_message_text' => $fundConfig->contact_info_message_text,
            'backoffice_enabled' => $fundConfig->backoffice_enabled,
            'auth_2fa_policy' => $fundConfig->auth_2fa_policy,
            'auth_2fa_remember_ip' => $fundConfig->auth_2fa_remember_ip,
            'allow_preset_amounts' => $fundConfig->allow_preset_amounts,
            'allow_preset_amounts_validator' => $fundConfig->allow_preset_amounts_validator,
            'allow_custom_amounts' => $fundConfig->allow_custom_amounts,
            'allow_custom_amounts_validator' => $fundConfig->allow_custom_amounts_validator,
            'custom_amount_min' => $fundConfig->custom_amount_min,
            'custom_amount_max' => $fundConfig->custom_amount_max,
        ], 'fund_config_');
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    protected function fundRequestMeta(FundRequest $fundRequest): array
    {
        return $this->keyPrepend([
            'id' => $fundRequest->id,
            'note' => $fundRequest->note,
            'state' => $fundRequest->state,
            'amount' => $fundRequest->amount,
            'fund_amount_preset_id' => $fundRequest->fund_amount_preset_id,
            'employee_id' => $fundRequest->employee_id,
            'disregard_note' => $fundRequest->disregard_note,
            'disregard_notify' => $fundRequest->disregard_notify,
            'created_date' => $fundRequest->created_at->format('Y-m-d'),
            'created_date_locale' => format_date_locale($fundRequest->created_at),
        ], 'fund_request_');
    }

    /**
     * @param FundPeriod $fundPeriod
     * @return array
     */
    protected function fundPeriodMeta(FundPeriod $fundPeriod): array
    {
        return $this->keyPrepend([
            ...$fundPeriod->only('id', 'state', 'fund_id'),
            'start_date' => $fundPeriod->start_date?->format('Y-m-d'),
            'end_date' => $fundPeriod->end_date?->format('Y-m-d'),
        ], 'fund_period_');
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @return array
     */
    protected function fundRequestRecordMeta(FundRequestRecord $fundRequestRecord): array
    {
        return $this->keyPrepend([
            'id' => $fundRequestRecord->id,
            'value' => $fundRequestRecord->value,
            'record_type_key' => $fundRequestRecord->record_type_key,
            'fund_criterion_id' => $fundRequestRecord->fund_criterion_id,
        ], 'fund_request_record_');
    }

    /**
     * @param FundRequestClarification $fundRequest
     * @return array
     */
    protected function fundRequestClarificationMeta(FundRequestClarification $fundRequest): array
    {
        return $this->keyPrepend([
            'id' => $fundRequest->id,
            'question' => $fundRequest->question,
        ], 'fund_request_clarification_');
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function providerMeta(Organization $provider): array
    {
        return $this->keyPrepend([
            'id' => $provider->id,
            'name' => $provider->name,
            'email' => $provider->email,
            'phone' => $provider->phone,
        ], 'provider_');
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function sponsorMeta(Organization $provider): array
    {
        return $this->keyPrepend([
            'id' => $provider->id,
            'name' => $provider->name,
            'phone' => $provider->phone,
            'email' => $provider->email,
        ], 'sponsor_');
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function organizationMeta(Organization $organization): array
    {
        return $this->keyPrepend([
            'id' => $organization->id,
            'name' => $organization->name,
        ], 'organization_');
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function productMeta(Product $product): array
    {
        return $this->keyPrepend([
            'id' => $product->id,
            'name' => $product->name,
            'price' => currency_format($product->price),
            'price_locale' => currency_format_locale($product->price),
        ], 'product_');
    }

    /**
     * @param Employee $employee
     * @return array
     */
    protected function employeeMeta(Employee $employee): array
    {
        return $this->keyPrepend([
            'id' => $employee->id,
            'roles' => $employee->roles->pluck('name')->join(', '),
            'email' => $employee->identity->email,
            'organization_id' => $employee->organization_id,
        ], 'employee_');
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function productReservationMeta(ProductReservation $reservation): array
    {
        return $this->keyPrepend([
            'id' => $reservation->id,
            'state' => $reservation->state,
            'amount' => currency_format($reservation->amount),
            'amount_locale' => currency_format_locale($reservation->amount),
        ], 'product_reservation_');
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function voucherMeta(Voucher $voucher): array
    {
        return $this->keyPrepend([
            'id' => $voucher->id,
            'number' => $voucher->number,
            'amount' => currency_format($voucher->amount_available),
            'amount_locale' => currency_format_locale($voucher->amount_available),
            'voucher_type' => $voucher->voucher_type,
            'fund_amount_preset_id' => $voucher->fund_amount_preset_id,
            'expire_date' => $voucher->last_active_day->format('Y-m-d'),
            'limit_multiplier' => $voucher->limit_multiplier,
            'expire_date_locale' => format_date_locale($voucher->last_active_day),
        ], 'voucher_');
    }

    /**
     * @param VoucherTransaction $transaction
     * @return array
     */
    protected function voucherTransactionMeta(VoucherTransaction $transaction): array
    {
        return $this->keyPrepend(array_merge([
            'id' => $transaction->id,
            'amount' => currency_format($transaction->amount),
            'amount_locale' => currency_format_locale($transaction->amount),
            'iban_to' => $transaction->iban_to,
            'iban_from' => $transaction->iban_from,
            'initiator' => $transaction->initiator,
            'target' => $transaction->target,
            'target_iban' => $transaction->target_iban,
            'upload_batch_id' => $transaction->upload_batch_id,
            'payment_time' => $transaction->payment_time,
            'description' => $transaction->description,
            'payment_time_locale' => format_date_locale($transaction->payment_time),
            'created_at' => $transaction->created_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale($transaction->created_at),
        ], $transaction->employee ? [
            'employee_id' => $transaction->employee_id,
            'employee_email' => $transaction->employee->identity->email,
        ] : []), 'voucher_transaction_');
    }

    /**
     * @param VoucherRecord $transaction
     * @return array
     */
    protected function voucherRecordMeta(VoucherRecord $transaction): array
    {
        return $this->keyPrepend([
            'id' => $transaction->id,
            'value' => $transaction->value,
            'voucher_id' => $transaction->voucher_id,
            'record_type_id' => $transaction->record_type_id,
            'record_type_key' => $transaction->record_type?->key,
            'deleted_at' => $transaction->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_at_locale' => format_datetime_locale($transaction->deleted_at),
            'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($transaction->created_at),
        ], 'voucher_record_');
    }

    /**
     * @param FundTopUpTransaction $transaction
     * @return array
     */
    protected function fundTopUpTransactionMeta(FundTopUpTransaction $transaction): array
    {
        return $this->keyPrepend([
            'amount' => $transaction->amount,
            'amount_locale' => currency_format($transaction->amount),
        ], 'fund_top_up_');
    }

    /**
     * @param PhysicalCard $physicalCard
     * @return array
     */
    protected function physicalCardMeta(PhysicalCard $physicalCard): array
    {
        return $this->keyPrepend([
            'id' => $physicalCard->id,
            'code' => $physicalCard->code,
            'voucher_id' => $physicalCard->voucher_id,
            'identity_address' => $physicalCard->identity_address,
        ], 'physical_card_');
    }

    /**
     * @param PhysicalCardRequest $physicalCardRequest
     * @return array
     */
    protected function physicalCardRequestMeta(PhysicalCardRequest $physicalCardRequest): array
    {
        return $this->keyPrepend([
            'id' => $physicalCardRequest->id,
            'city' => $physicalCardRequest->city,
            'house' => $physicalCardRequest->house,
            'address' => $physicalCardRequest->address,
            'postcode' => $physicalCardRequest->postcode,
            'house_addition' => $physicalCardRequest->house_addition,
        ], 'physical_card_request_');
    }

    /**
     * @param Bank $bank
     * @return array
     */
    protected function bankMeta(Bank $bank): array
    {
        return $this->keyPrepend([
            'id' => $bank->id,
            'name' => $bank->name,
        ], 'bank_');
    }

    /**
     * @param BankConnection $bankConnection
     * @return array
     */
    protected function bankConnectionMeta(BankConnection $bankConnection): array
    {
        return $this->keyPrepend([
            'id' => $bankConnection->id,
            'state' => $bankConnection->state,
            'bank_id' => $bankConnection->bank_id,
            'expire_at' => $bankConnection->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($bankConnection->expire_at),
            'implementation_id' => $bankConnection->implementation_id,
        ], 'bank_connection_');
    }

    /**
     * @param BankConnectionAccount $account
     * @return array
     */
    protected function bankConnectionAccountMeta(BankConnectionAccount $account): array
    {
        return $this->keyPrepend([
            'id' => $account->id,
            'monetary_account_id' => $account->monetary_account_id,
            'monetary_account_iban' => $account->monetary_account_iban,
            'monetary_account_name' => $account->monetary_account_name,
        ], 'bank_connection_account_');
    }

    /**
     * @param VoucherTransactionBulk $bulk
     * @return array
     */
    protected function voucherTransactionBulkMeta(VoucherTransactionBulk $bulk): array
    {
        return $this->keyPrepend([
            'id' => $bulk->id,
            'code' => $bulk->code,
            'state' => $bulk->state,
            'sepa_xml' => $bulk->sepa_xml,
            'auth_url' => $bulk->auth_url,
            'auth_params' => $bulk->auth_params,
            'payment_id' => $bulk->payment_id,
            'access_token' => $bulk->access_token,
            'execution_date' => $bulk->execution_date,
            'redirect_token' => $bulk->redirect_token,
            'monetary_account_id' => $bulk->monetary_account_id,
        ], 'transaction_bulk_');
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function implementationMeta(Implementation $implementation): array
    {
        return $this->keyPrepend([
            'id' => $implementation->id,
            'key' => $implementation->key,
            'name' => $implementation->name,
        ], 'implementation_');
    }

    /**
     * @param Reimbursement $reimbursement
     * @return array
     */
    protected function reimbursementMeta(Reimbursement $reimbursement): array
    {
        return $this->keyPrepend([
            'id' => $reimbursement->id,
            'code' => $reimbursement->code,
            'title' => $reimbursement->title,
            'amount' => $reimbursement->amount,
            'employee_id' => $reimbursement->employee_id,
            'iban' => $reimbursement->iban,
            'iban_name' => $reimbursement->iban_name,
            'reason' => $reimbursement->reason ?: '',
            'state' => $reimbursement->state,
            'submitted_at' => $reimbursement->submitted_at?->format('Y-m-d H:i:s'),
            'resolved_at' => $reimbursement->resolved_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $reimbursement->deleted_at?->format('Y-m-d H:i:s'),
        ], 'reimbursement_');
    }

    /**
     * @param MollieConnection $mollieConnection
     * @return array
     */
    protected function mollieConnectionMeta(MollieConnection $mollieConnection): array
    {
        return $this->keyPrepend([
            'id' => $mollieConnection->id,
            'business_type' => $mollieConnection->business_type,
            'onboarding_state' => $mollieConnection->onboarding_state,
            'connection_state' => $mollieConnection->connection_state,
            'mollie_organization_id' => $mollieConnection->mollie_organization_id,
        ], 'mollie_connection_');
    }

    /**
     * @param BIConnection $connection
     * @return array
     */
    protected function biConnectionMeta(BIConnection $connection): array
    {
        return $this->keyPrepend([
            'id' => $connection->id,
            'ips' => $connection->ips,
            'enabled' => $connection->enabled,
            'expire_at' => $connection->expire_at,
            'data_types' => $connection->data_types,
            'expiration_period' => $connection->expiration_period,
        ], 'bi_connection_');
    }

    /**
     * @param ReservationExtraPayment $extraPayment
     * @return array
     */
    protected function reservationExtraPaymentMeta(ReservationExtraPayment $extraPayment): array
    {
        return $this->keyPrepend([
            'id' => $extraPayment->id,
            'type' => $extraPayment->type,
            'state' => $extraPayment->state,
            'method' => $extraPayment->method,
            'currency' => $extraPayment->currency,
            'amount' => $extraPayment->amount,
            'amount_locale' => $extraPayment->amount_locale,
            'amount_refunded' => $extraPayment->amount_refunded,
            'amount_refunded_locale' => $extraPayment->amount_refunded_locale,
            'paid_at' => $extraPayment->paid_at,
            'canceled_at' => $extraPayment->canceled_at,
            'product_reservation_id' => $extraPayment->product_reservation_id,
        ], 'reservation_extra_payment_');
    }

    /**
     * @param array $data
     * @param string $prefix
     * @return array
     */
    protected function keyPrepend(array $data, string $prefix): array
    {
        return array_reduce(array_keys($data), fn ($arr, $key) => array_merge($arr, [
            $prefix . $key => $data[$key],
        ]), []);
    }
}
