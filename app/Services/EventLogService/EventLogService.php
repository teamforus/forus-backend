<?php

namespace App\Services\EventLogService;

use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Models\Employee;
use App\Models\Fund;
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
use App\Models\Voucher;
use App\Models\VoucherTransactionBulk;
use App\Models\VoucherTransaction;
use App\Services\BankService\Models\Bank;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Interfaces\IEventLogService;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EventLogService
 * @package App\Services\EventLogService
 */
class EventLogService implements IEventLogService
{
    /**
     * @param HasLogs|Model $loggable
     * @param string $action
     * @param array $models
     * @param array $raw_meta
     * @return mixed
     */
    public function log(
        Model $loggable,
        string $action,
        array $models = [],
        array $raw_meta = []
    ): EventLog {
        $meta = array_reduce(array_keys($models), function($carry, $key) use ($models) {
            return array_merge($carry, $this->modelToMeta($key, $models[$key]));
        }, []);

        return $loggable->log($action, array_merge($meta, $raw_meta));
    }

    /**
     * @param string $type
     * @param $model
     * @return array
     */
    public function modelToMeta(string $type, $model): array
    {
        $modelMeta = [
            'fund' => fn() => $this->fundMeta($model),
            'fund_request' => fn() => $this->fundRequestMeta($model),
            'fund_request_record' => fn() => $this->fundRequestRecordMeta($model),
            'fund_request_clarification' => fn() => $this->fundRequestClarificationMeta($model),
            'fund_top_up_transaction' => fn() => $this->fundTopUpTransactionMeta($model),
            'sponsor' => fn() => $this->sponsorMeta($model),
            'provider' => fn() => $this->providerMeta($model),
            'product' => fn() => $this->productMeta($model),
            'voucher' => fn() => $this->voucherMeta($model),
            'organization' => fn() => $this->organizationMeta($model),
            'employee' => fn() => $this->employeeMeta($model),
            'product_reservation' => fn() => $this->productReservationMeta($model),
            'voucher_transaction' => fn() => $this->voucherTransactionMeta($model),
            'physical_card' => fn() => $this->physicalCardMeta($model),
            'physical_card_request' => fn() => $this->physicalCardRequestMeta($model),
            'bank' => fn() => $this->bankMeta($model),
            'bank_connection' => fn() => $this->bankConnectionMeta($model),
            'bank_connection_account' => fn() => $this->bankConnectionAccountMeta($model),
            'voucher_transaction_bulk' => fn() => $this->voucherTransactionBulkMeta($model),
            'implementation' => fn() => $this->implementationMeta($model),
        ];

        return $modelMeta[$type] ? $modelMeta[$type]() : [];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundMeta(Fund $fund): array
    {
        return [
            'fund_id' => $fund->id,
            'fund_name' => $fund->name,
            'fund_type' => $fund->type,
            'fund_start_date' => $fund->start_date->format('Y-m-d'),
            'fund_end_date' => $fund->start_date->clone()->addDay()->format('Y-m-d'),
            'fund_end_date_minus1' => $fund->end_date->format('Y-m-d'),
            'fund_start_date_locale' => format_date_locale($fund->start_date),
            'fund_end_date_locale' => format_date_locale($fund->end_date->clone()->addDay()),
            'fund_end_date_minus1_locale' => format_date_locale($fund->end_date),
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    protected function fundRequestMeta(FundRequest $fundRequest): array
    {
        return [
            'fund_request_id' => $fundRequest->id,
            'fund_request_note' => $fundRequest->note,
            'fund_request_state' => $fundRequest->state,
            'fund_request_disregard_note' => $fundRequest->disregard_note,
            'fund_request_disregard_notify' => $fundRequest->disregard_notify,
            'fund_request_created_date' => $fundRequest->created_at->format('Y-m-d'),
            'fund_request_created_date_locale' => format_date_locale($fundRequest->created_at),
        ];
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @return array
     */
    protected function fundRequestRecordMeta(FundRequestRecord $fundRequestRecord): array
    {
        return [
            'fund_request_record_id' => $fundRequestRecord->id,
            'fund_request_record_note' => $fundRequestRecord->note,
            'fund_request_record_value' => $fundRequestRecord->value,
            'fund_request_record_state' => $fundRequestRecord->state,
            'fund_request_record_employee_id' => $fundRequestRecord->employee_id,
            'fund_request_record_record_type_key' => $fundRequestRecord->record_type_key,
            'fund_request_record_fund_criterion_id' => $fundRequestRecord->fund_criterion_id,
        ];
    }

    /**
     * @param FundRequestClarification $fundRequest
     * @return array
     */
    protected function fundRequestClarificationMeta(FundRequestClarification $fundRequest): array
    {
        return [
            'fund_request_clarification_id' => $fundRequest->id,
            'fund_request_clarification_question' => $fundRequest->question,
        ];
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function providerMeta(Organization $provider): array
    {
        return [
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'provider_email' => $provider->email,
            'provider_phone' => $provider->phone,
        ];
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function sponsorMeta(Organization $provider): array
    {
        return [
            'sponsor_id' => $provider->id,
            'sponsor_name' => $provider->name,
            'sponsor_phone' => $provider->phone,
            'sponsor_email' => $provider->email,
        ];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function organizationMeta(Organization $organization): array
    {
        return [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function productMeta(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => currency_format($product->price),
            'product_price_locale' => currency_format_locale($product->price),
        ];
    }

    /**
     * @param Employee $employee
     * @return array
     */
    protected function employeeMeta(Employee $employee): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_roles' => $employee->roles->pluck('name')->join(', '),
            'employee_email' => record_repo()->primaryEmailByAddress($employee->identity_address),
        ];
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function productReservationMeta(ProductReservation $reservation): array
    {
        return [
            'product_reservation_id' => $reservation->id,
            'product_reservation_state' => $reservation->state,
            'product_reservation_amount' => currency_format($reservation->amount),
            'product_reservation_amount_locale' => currency_format_locale($reservation->amount),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function voucherMeta(Voucher $voucher): array
    {
        return [
            'voucher_id' => $voucher->id,
            'voucher_amount' => currency_format($voucher->amount_available),
            'voucher_amount_locale' => currency_format_locale($voucher->amount_available),
            'voucher_expire_date' => $voucher->last_active_day->format('Y-m-d'),
            'voucher_expire_date_locale' => format_date_locale($voucher->last_active_day),
        ];
    }

    /**
     * @param VoucherTransaction $voucherTransaction
     * @return array
     */
    protected function voucherTransactionMeta(VoucherTransaction $voucherTransaction): array
    {
        return [
            'voucher_transaction_id' => $voucherTransaction->id,
            'voucher_transaction_amount' => $voucherTransaction->amount,
            'voucher_transaction_amount_locale' => currency_format_locale($voucherTransaction->amount),
            'voucher_transaction_iban_to' => $voucherTransaction->iban_to,
            'voucher_transaction_iban_from' => $voucherTransaction->iban_from,
            'voucher_transaction_payment_time' => $voucherTransaction->payment_time,
            'voucher_transaction_payment_time_locale' => format_date_locale($voucherTransaction->payment_time),
            'voucher_transaction_created_at' => $voucherTransaction->created_at->format('Y-m-d'),
            'voucher_transaction_created_at_locale' => format_date_locale($voucherTransaction->created_at),
        ];
    }

    /**
     * @param FundTopUpTransaction $transaction
     * @return array
     */
    protected function fundTopUpTransactionMeta(FundTopUpTransaction $transaction): array
    {
        return [
            'fund_top_up_amount' => $transaction->amount,
            'fund_top_up_amount_locale' => currency_format($transaction->amount),
        ];
    }

    /**
     * @param PhysicalCard $physicalCard
     * @return array
     */
    protected function physicalCardMeta(PhysicalCard $physicalCard): array
    {
        return [
            'physical_card_id'                  => $physicalCard->id,
            'physical_card_code'                => $physicalCard->code,
            'physical_card_voucher_id'          => $physicalCard->voucher_id,
            'physical_card_identity_address'    => $physicalCard->identity_address,
        ];
    }

    /**
     * @param PhysicalCardRequest $physicalCardRequest
     * @return array
     */
    protected function physicalCardRequestMeta(PhysicalCardRequest $physicalCardRequest): array
    {
        return [
            'physical_card_request_id'              => $physicalCardRequest->id,
            'physical_card_request_address'         => $physicalCardRequest->address,
            'physical_card_request_house'           => $physicalCardRequest->house,
            'physical_card_request_postcode'        => $physicalCardRequest->postcode,
            'physical_card_request_city'            => $physicalCardRequest->city,
            'physical_card_request_house_addition'  => $physicalCardRequest->house_addition,
        ];
    }

    /**
     * @param Bank $bank
     * @return array
     */
    protected function bankMeta(Bank $bank): array
    {
        return [
            'bank_id' => $bank->id,
            'bank_name' => $bank->name,
        ];
    }

    /**
     * @param BankConnection $bankConnection
     * @return array
     */
    protected function bankConnectionMeta(BankConnection $bankConnection): array
    {
        $expire_at = $bankConnection->session_expire_at;

        return array_merge([
            'bank_connection_id' => $bankConnection->id,
            'bank_connection_state' => $bankConnection->state,
            'bank_connection_bank_id' => $bankConnection->bank_id,
            'bank_connection_session_expire_at' => $expire_at ? $expire_at->format('Y-m-d H:i:s') : null,
            'bank_connection_implementation_id' => $bankConnection->implementation_id,
        ]);
    }

    /**
     * @param BankConnectionAccount $bankConnectionAccount
     * @return array
     */
    protected function bankConnectionAccountMeta(BankConnectionAccount $bankConnectionAccount): array
    {
        return array_merge([
            'bank_connection_account_id' => $bankConnectionAccount->id,
            'bank_connection_account_monetary_account_id' => $bankConnectionAccount->monetary_account_id,
            'bank_connection_account_monetary_account_iban' => $bankConnectionAccount->monetary_account_iban,
        ]);
    }

    /**
     * @param VoucherTransactionBulk $transactionBulk
     * @return array
     */
    protected function voucherTransactionBulkMeta(VoucherTransactionBulk $transactionBulk): array
    {
        return [
            'transaction_bulk_id' => $transactionBulk->id,
            'transaction_bulk_state' => $transactionBulk->state,
            'transaction_bulk_payment_id' => $transactionBulk->payment_id,
            'transaction_bulk_monetary_account_id' => $transactionBulk->monetary_account_id,
        ];
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function implementationMeta(Implementation $implementation): array
    {
        return [
            'implementation_id' => $implementation->id,
            'implementation_key' => $implementation->key,
            'implementation_name' => $implementation->name,
        ];
    }
}