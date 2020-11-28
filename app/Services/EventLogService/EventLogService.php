<?php

namespace App\Services\EventLogService;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundTopUpTransaction;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Interfaces\IEventLogService;
use App\Services\EventLogService\Traits\HasLogs;

/**
 * Class EventLogService
 * @package App\Services\EventLogService
 */
class EventLogService implements IEventLogService
{
    /**
     * @param HasLogs $loggable
     * @param string $action
     * @param array $models
     * @param array $raw_meta
     * @return mixed
     */
    public function log($loggable, string $action, array $models = [], array $raw_meta = []): EventLog {
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
        $modelMeta = [];

        switch ($type) {
            case 'fund': $modelMeta = $this->fundMeta($model); break;
            case 'fund_request': $modelMeta = $this->fundRequestMeta($model); break;
            case 'fund_request_clarification': $modelMeta = $this->fundRequestClarificationMeta($model); break;
            case 'top_up_transaction': $modelMeta = $this->fundTopUpTransactionMeta($model); break;
            case 'sponsor': $modelMeta = $this->sponsorMeta($model); break;
            case 'provider': $modelMeta = $this->providerMeta($model); break;
            case 'product': $modelMeta = $this->productMeta($model); break;
            case 'voucher': $modelMeta = $this->voucherMeta($model); break;
            case 'organization': $modelMeta = $this->organizationMeta($model); break;
            case 'employee': $modelMeta = $this->employeeMeta($model); break;
        }

        return $modelMeta;
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function fundMeta(Fund $fund): array {
        return [
            'fund_id' => $fund->id,
            'fund_name' => $fund->name,
            'fund_start_date' => $fund->start_date->format('Y-m-d'),
            'fund_end_date' => $fund->start_date->format('Y-m-d'),
            'fund_end_date_minus1' => $fund->end_date->subDay()->clone()->format('Y-m-d'),
            'fund_start_date_locale' => format_date_locale($fund->start_date),
            'fund_end_date_locale' => format_date_locale($fund->end_date),
            'fund_end_date_minus1_locale' => format_date_locale($fund->end_date->clone()->subDay()),
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    protected function fundRequestMeta(FundRequest $fundRequest): array {
        return [
            'fund_request_id' => $fundRequest->id,
            'fund_request_note' => $fundRequest->note,
            'fund_request_state' => $fundRequest->state,
        ];
    }

    /**
     * @param FundRequestClarification $fundRequest
     * @return array
     */
    protected function fundRequestClarificationMeta(
        FundRequestClarification $fundRequest
    ): array {
        return [
            'fund_request_clarification_id' => $fundRequest->id,
            'fund_request_clarification_question' => $fundRequest->question,
        ];
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function providerMeta(Organization $provider): array {
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
    protected function sponsorMeta(Organization $provider): array {
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
    protected function organizationMeta(Organization $organization): array {
        return [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function productMeta(Product $product): array {
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
    protected function employeeMeta(Employee $employee): array {
        return [
            'employee_id' => $employee->id,
            'employee_roles' => $employee->roles->pluck('name')->join(', '),
            'employee_email' => record_repo()->primaryEmailByAddress(
                $employee->identity_address
            ),
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function voucherMeta(Voucher $voucher): array {
        return [
            'voucher_id' => $voucher->id,
            'voucher_amount' => currency_format($voucher->amount_available),
            'voucher_amount_locale' => currency_format_locale($voucher->amount_available),
            'voucher_expire_date' => $voucher->last_active_day->format('Y-m-d'),
            'voucher_expire_date_locale' => format_date_locale($voucher->last_active_day),
        ];
    }

    /**
     * @param FundTopUpTransaction $transaction
     * @return array
     */
    protected function fundTopUpTransactionMeta(FundTopUpTransaction $transaction): array {
        return [
            'fund_top_up_amount' => $transaction->amount,
            'fund_top_up_amount_locale' => currency_format($transaction->amount),
        ];
    }
}