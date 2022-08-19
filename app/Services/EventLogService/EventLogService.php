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

class EventLogService implements IEventLogService
{
    /**
     * @param HasLogs|Model|mixed $loggable
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
        return $this->keyPrepend([
            'id' => $fund->id,
            'name' => $fund->name,
            'type' => $fund->type,
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->start_date->clone()->addDay()->format('Y-m-d'),
            'end_date_minus1' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date->clone()->addDay()),
            'end_date_minus1_locale' => format_date_locale($fund->end_date),
        ], 'fund_');
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
            'disregard_note' => $fundRequest->disregard_note,
            'disregard_notify' => $fundRequest->disregard_notify,
            'created_date' => $fundRequest->created_at->format('Y-m-d'),
            'created_date_locale' => format_date_locale($fundRequest->created_at),
        ], 'fund_request_');
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @return array
     */
    protected function fundRequestRecordMeta(FundRequestRecord $fundRequestRecord): array
    {
        return $this->keyPrepend([
            'id' => $fundRequestRecord->id,
            'note' => $fundRequestRecord->note,
            'value' => $fundRequestRecord->value,
            'state' => $fundRequestRecord->state,
            'employee_id' => $fundRequestRecord->employee_id,
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
            'amount' => currency_format($voucher->amount_available),
            'amount_locale' => currency_format_locale($voucher->amount_available),
            'expire_date' => $voucher->last_active_day->format('Y-m-d'),
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
            'amount' => $transaction->amount,
            'amount_locale' => currency_format_locale($transaction->amount),
            'iban_to' => $transaction->iban_to,
            'iban_from' => $transaction->iban_from,
            'initiator' => $transaction->initiator,
            'target' => $transaction->target,
            'payment_time' => $transaction->payment_time,
            'payment_time_locale' => format_date_locale($transaction->payment_time),
            'created_at' => $transaction->created_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale($transaction->created_at),
        ], $transaction->employee ? [
            'employee_id' => $transaction->employee_id,
            'employee_email' => $transaction->employee->identity->email,
        ] : []), 'voucher_transaction_');
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
        ], 'physical_card_request_i');
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
        $expire_at = $bankConnection->session_expire_at;

        return $this->keyPrepend([
            'id' => $bankConnection->id,
            'state' => $bankConnection->state,
            'bank_id' => $bankConnection->bank_id,
            'session_expire_at' => $expire_at?->format('Y-m-d H:i:s'),
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
     * @param array $data
     * @param string $prefix
     * @return array
     */
    protected function keyPrepend(array $data, string $prefix): array
    {
        return array_reduce(array_keys($data), fn($arr, $key) => array_merge($arr, [
            $prefix . $key => $data[$key],
        ]), []);
    }
}