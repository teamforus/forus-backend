<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Services\EventLogService\Models\EventLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * @property Voucher $resource
 */
class VoucherResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const LOAD = [
        'logs',
        'parent',
        'tokens',
        'token_with_confirmation',
        'token_without_confirmation',
        'last_transaction',
        'transactions.voucher.fund.logo.presets',
        'transactions.provider.logo.presets',
        'transactions.product.photo.presets',
        'product_vouchers.product.photo.presets',
        'product_vouchers.tokens',
        'product_vouchers.token_with_confirmation',
        'product_vouchers.token_without_confirmation',
        'product.photo.presets',
        'product.product_category.translations',
        'product.organization.business_type.translations',
        'product.organization.logo.presets',
        'product.organization.offices.schedules',
        'product.organization.offices.photo.presets',
        'product.organization.offices.organization.logo.presets',
        'fund.fund_config.implementation',
        'fund.provider_organizations_approved.offices.schedules',
        'fund.provider_organizations_approved.offices.photo.presets',
        'fund.provider_organizations_approved.offices.organization.logo.presets',
        'fund.logo.presets',
        'fund.organization.logo.presets',
        'physical_cards',
        'last_deactivation_log',
        'voucher_records.record_type',
    ];

    public const LOAD_COUNT = [
        'transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $voucher = $this->resource;
        $physical_cards = $voucher->physical_cards[0] ?? null;
        $deactivationDate = $voucher->deactivated ? $this->getDeactivationDate($voucher): null;

        return array_merge($voucher->only([
            'id', 'identity_address', 'fund_id', 'returnable', 'transactions_count',
            'expired', 'deactivated', 'type', 'state', 'state_locale', 'is_external',
        ]), $this->getBaseFields($voucher), $this->getOptionalFields($voucher), [
            'deactivated_at' => $deactivationDate?->format('Y-m-d'),
            'deactivated_at_locale' => format_date_locale($deactivationDate),
            'history' => $this->getStateHistory($voucher),
            'expire_at' => [
                'date' => $voucher->expire_at->format("Y-m-d H:i:s.00000"),
                'timeZone' => $voucher->expire_at->timezone->getName(),
            ],
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'last_active_day' => $voucher->last_active_day->format('Y-m-d'),
            'last_active_day_locale' => format_date_locale($voucher->last_active_day),
            'last_transaction_at' => $voucher->last_transaction?->created_at->format('Y-m-d'),
            'last_transaction_at_locale' => $voucher->last_transaction ? format_date_locale(
                $voucher->last_transaction->created_at
            ) : null,
            'address' => $voucher->token_with_confirmation->address,
            'address_printable' => $voucher->token_without_confirmation->address,
            'timestamp' => $voucher->created_at->timestamp,
            'fund' => $this->getFundResource($voucher->fund),
            'parent' => $voucher->parent ? array_merge($voucher->parent->only('identity_address', 'fund_id'), [
                'created_at' => $voucher->parent->created_at_string
            ]) : null,
            'physical_card' => $physical_cards ? $physical_cards->only('id', 'code') : false,
            'product_vouchers' => $this->getProductVouchers($voucher->product_vouchers),
            'query_product' => $this->queryProduct($voucher, $request->get('product_id')),
        ], array_merge(
             $this->getRecords($voucher),
            $this->timestamps($voucher, 'created_at'),
        ));
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getRecords(Voucher $voucher): array
    {
        if (!$voucher->fund?->fund_config?->allow_voucher_records) {
            return [];
        }

        $records = $voucher->voucher_records->sortBy(['record_type_id']);
        $recordsMap = $records->pluck('value', 'record_type.key');
        $givenName = Arr::get($recordsMap, 'given_name');
        $familyName = Arr::get($recordsMap, 'family_name');

        return [
            'records' => VoucherRecordResource::collection($records),
            'records_title' => $givenName ? strtoupper($givenName[0]) . '. ' . $familyName : null,
        ];
    }

    /**
     * @param Voucher $voucher
     * @return Carbon|null
     */
    protected function getDeactivationDate(Voucher $voucher): ?Carbon
    {
        return $voucher->last_deactivation_log?->created_at;
    }

    /**
     * @param Voucher $voucher
     * @return \Illuminate\Support\Collection
     */
    protected function getStateHistory(Voucher $voucher): \Illuminate\Support\Collection
    {
        $logs = $voucher->requesterHistoryLogs();

        return $logs->map(function(EventLog $eventLog) use ($voucher) {
            return array_merge($eventLog->only('id', 'event'), [
                'event_locale' => $eventLog->eventDescriptionLocaleWebshop(),
                'created_at' => $eventLog->created_at->format('Y-m-d'),
                'created_at_locale' => format_date_locale($eventLog->created_at),
            ]);
        })->values();
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getBaseFields(Voucher $voucher): array
    {
        if ($voucher->isBudgetType()) {
            $amount = $voucher->fund->isTypeBudget() ? $voucher->amount_available_cached : 0;
            $used = $voucher->fund->isTypeBudget() ? $amount == 0 : null;
            $productResource = null;
        } elseif ($voucher->type === 'product') {
            $used = $voucher->transactions_count > 0;
            $amount = $voucher->amount;
            $productResource = array_merge($voucher->product->only([
                'id', 'name', 'description', 'description_html', 'price', 'price_locale',
                'total_amount', 'sold_amount', 'product_category_id',
                'organization_id'
            ]), [
                'price_locale' => $voucher->product->priceLocale($voucher->fund->getImplementation()),
                'product_category' => $voucher->product->product_category,
                'expire_at' => $voucher->product->expire_at ? $voucher->product->expire_at->format('Y-m-d') : '',
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicWithPrivateResource($voucher->product->organization),
            ]);
        } else {
            abort("Unknown voucher type!", 403);
        }

        return [
            'used' => $used,
            'amount' => currency_format($amount),
            'amount_locale' => currency_format_locale($amount, $voucher->fund->getImplementation()),
            'product' => $productResource,
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getOptionalFields(Voucher $voucher): array
    {
        return [
            'transactions' => $this->getTransactions($voucher),
            'offices' => $this->getOffices($voucher),
        ];
    }

    /**
     * @param Voucher $voucher
     * @param int|null $product_id
     * @return array|null
     * @throws \Exception
     */
    public function queryProduct(Voucher $voucher, ?int $product_id = null): ?array
    {
        /** @var Product|null $product */
        $product = $product_id ? ProductSubQuery::appendReservationStats([
            'voucher_id' => $voucher->id
        ], Product::whereId($product_id))->first() : null;

        if (!$product) {
            return null;
        }

        $expire_at = $voucher->calcExpireDateForProduct($product);
        $reservable_count = $product['limit_available'] ?? null;
        $reservable_count = is_numeric($reservable_count) ? intval($reservable_count) : null;
        $reservable_expire_at = $expire_at?->format('Y-m-d');
        $allow_reservations = $voucher->fund->fund_config->allow_reservations;
        $reservable_enabled = $allow_reservations && $product->reservationsEnabled($voucher->fund);
        $reservable = $reservable_count === null;

        if ($voucher->isBudgetType() && $reservable_count !== null) {
            $reservable = FundQuery::whereProductsAreApprovedAndActiveFilter(
                Fund::whereId($voucher->fund_id), $product
            )->exists() && $reservable_count > 0;
        }

        if (!$voucher->fund->isTypeSubsidy()) {
            $reservable = $reservable && $voucher->amount_available >= $product->price;
        }

        return [
            'reservable' => $reservable_enabled && $reservable,
            'reservable_count' => $reservable_count,
            'reservable_enabled' => $reservable_enabled,
            'reservable_expire_at' => $reservable_expire_at,
            'reservable_expire_at_locale' => format_date_locale($reservable_expire_at ?? null),
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getFundResource(Fund $fund): array
    {
        return array_merge($fund->only('id', 'name', 'state', 'type'), [
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => new MediaCompactResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d H:i'),
            'start_date_locale' => format_datetime_locale($fund->start_date),
            'end_date' => $fund->end_date->format('Y-m-d H:i'),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationBasicWithPrivateResource($fund->organization),
            'allow_physical_cards' => $fund->fund_config->allow_physical_cards,
            'allow_blocking_vouchers' => $fund->fund_config->allow_blocking_vouchers,
        ], $fund->fund_config->only([
            'allow_reimbursements', 'allow_reservations', 'key',
        ]));
    }

    /**
     * @param Collection|Voucher[]|null $product_vouchers
     * @return Voucher[]|\Illuminate\Support\Collection|null
     */
    protected function getProductVouchers(
        Collection|array|null $product_vouchers
    ): \Illuminate\Support\Collection|array|null {
        return $product_vouchers?->map(function (Voucher $product_voucher) {
            return array_merge($product_voucher->only([
                'identity_address', 'fund_id', 'returnable',
            ]), [
                'address' => $product_voucher->token_with_confirmation->address,
                'amount' => currency_format($product_voucher->amount),
                'date' => $product_voucher->created_at->format('M d, Y'),
                'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                'timestamp' => $product_voucher->created_at->timestamp,
                'product' => self::getProductDetails($product_voucher),
            ], $this->timestamps($product_voucher, 'created_at'));
        })->values();
    }

    /**
     * @param Voucher $product_voucher
     * @return array
     */
    protected static function getProductDetails(Voucher $product_voucher): array
    {
        return array_merge($product_voucher->product->only([
            'id', 'name', 'description', 'total_amount',
            'sold_amount', 'product_category_id', 'organization_id'
        ]), $product_voucher->fund->isTypeBudget() ? [
            'price' => currency_format($product_voucher->product->price),
        ] : []);
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getTransactions(Voucher $voucher): AnonymousResourceCollection
    {
        $hideOnMeApp = Config::get('forus.features.me_app.hide_non_provider_transactions');

        if ($hideOnMeApp && BaseFormRequest::createFromBase(request())->isMeApp()) {
            return  VoucherTransactionResource::collection(
                $voucher->all_transactions->where('target', 'provider')
            );
        }

        return VoucherTransactionResource::collection($voucher->all_transactions);
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getOffices(Voucher $voucher): AnonymousResourceCollection
    {
        if ($voucher->isBudgetType()) {
            return OfficeResource::collection(
                $voucher->fund->provider_organizations_approved->pluck('offices')->flatten()
            );
        }

        return OfficeResource::collection($voucher->product->organization->offices);
    }
}
