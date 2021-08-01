<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Services\EventLogService\Models\EventLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class VoucherResource
 * @property Voucher $resource
 * @package App\Http\Resources
 */
class VoucherResource extends Resource
{
    /**
     * @var array
     */
    public static $load = [
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
    ];

    /**
     * @var array
     */
    public static $loadCount = [
        'transactions',
    ];

    /**
     * @return array|string[]
     */
    public static function load(): array
    {
        return self::$load;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request|any $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request): array
    {
        $voucher = $this->resource;
        $physical_cards = $voucher->physical_cards->first();
        $deactivationDate = $voucher->deactivated ? $this->getDeactivationDate($voucher): null;

        return array_merge($voucher->only([
            'identity_address', 'fund_id', 'returnable', 'transactions_count',
            'expired', 'deactivated', 'type', 'state', 'state_locale',
        ]), $this->getBaseFields($voucher), [
            'deactivated_at' => $deactivationDate ? $deactivationDate->format('Y-m-d') : null,
            'deactivated_at_locale' => format_date_locale($deactivationDate),
            'history' => $this->getStateHistory($voucher),
            'created_at' => $voucher->created_at_string,
            'created_at_locale' => $voucher->created_at_string_locale,
            'expire_at' => [
                'date' => $voucher->expire_at->format("Y-m-d H:i:s.00000"),
                'timeZone' => $voucher->expire_at->timezone->getName(),
            ],
            'expire_at_locale' => format_date_locale($voucher->expire_at),
            'last_active_day' => $voucher->last_active_day->format('Y-m-d'),
            'last_active_day_locale' => format_date_locale($voucher->last_active_day),
            'last_transaction_at' => $voucher->last_transaction ?
                $voucher->last_transaction->created_at->format('Y-m-d') : null,
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
            'physical_card' => $physical_cards ? $physical_cards->only(['id', 'code']) : false,
            'product_vouchers' => $this->getProductVouchers($voucher->product_vouchers),
            'transactions' => $this->getTransactions($voucher),
            'query_product' => $this->queryProduct($voucher, $request->get('product_id')),
        ]);
    }

    /**
     * @param Voucher $voucher
     * @return Carbon|null
     */
    protected function getDeactivationDate(Voucher $voucher): ?Carbon
    {
        return $voucher->last_deactivation_log ? $voucher->last_deactivation_log->created_at : null;
    }

    /**
     * @param Voucher $voucher
     * @return Collection|\Illuminate\Support\Collection
     */
    protected function getStateHistory(Voucher $voucher)
    {
        $logs = $voucher->requesterHistoryLogs()->sortByDesc('created_at');

        return $logs->map(function(EventLog $eventLog) use ($voucher) {
            return array_merge($eventLog->only('id', 'event', 'event_locale'), [
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
                'id', 'name', 'description', 'description_html', 'price',
                'total_amount', 'sold_amount', 'product_category_id',
                'organization_id'
            ]), [
                'product_category' => $voucher->product->product_category,
                'expire_at' => $voucher->product->expire_at ? $voucher->product->expire_at->format('Y-m-d') : '',
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicWithPrivateResource($voucher->product->organization),
            ]);
        } else {
            abort("Unknown voucher type!", 403);
            exit();
        }

        return [
            'used' => $used,
            'amount' => currency_format($amount),
            'offices' => $this->getOffices($voucher),
            'product' => $productResource,
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
        $reservable = false;
        $reservable_count = $product['limit_available'] ?? null;
        $reservable_count = is_numeric($reservable_count) ? intval($reservable_count) : null;
        $reservable_expire_at = $expire_at ? $expire_at->format('Y-m-d') : null;
        $reservable_enabled = $product->reservationsEnabled($voucher->fund);

        if ($voucher->isBudgetType()) {
            if ($voucher->fund->isTypeSubsidy()) {
                $reservable = $reservable_count > 0;
            } else if ($voucher->fund->isTypeBudget()) {
                $reservable = FundQuery::whereProductsAreApprovedAndActiveFilter(
                    Fund::whereId($voucher->fund_id), $product
                )->exists() && $voucher->amount_available > $product->price;
            }
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
        return array_merge($fund->only([
            'id', 'name', 'state', 'type',
        ]), [
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => new MediaCompactResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d H:i'),
            'start_date_locale' => format_datetime_locale($fund->start_date),
            'end_date' => $fund->end_date->format('Y-m-d H:i'),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationBasicWithPrivateResource($fund->organization),
            'allow_physical_cards' => $fund->fund_config->allow_physical_cards,
        ]);
    }

    /**
     * @param Voucher[]|Collection|null $product_vouchers
     * @return Voucher[]|Collection|null
     */
    protected function getProductVouchers($product_vouchers)
    {
        return $product_vouchers ? $product_vouchers->map(static function(Voucher $product_voucher) {
            return array_merge($product_voucher->only([
                'identity_address', 'fund_id', 'returnable',
            ]), [
                'created_at' => $product_voucher->created_at_string,
                'created_at_locale' => $product_voucher->created_at_string_locale,
                'address' => $product_voucher->token_with_confirmation->address,
                'amount' => currency_format($product_voucher->amount),
                'date' => $product_voucher->created_at->format('M d, Y'),
                'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
                'timestamp' => $product_voucher->created_at->timestamp,
                'product' => self::getProductDetails($product_voucher),
            ]);
        })->values() : null;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    protected function getTransactions(Voucher $voucher): AnonymousResourceCollection
    {
        return VoucherTransactionResource::collection($voucher->transactions);
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getOffices(Voucher $voucher): AnonymousResourceCollection
    {
        if ($voucher->type === 'regular') {
            return OfficeResource::collection(
                $voucher->fund->provider_organizations_approved->pluck('offices')->flatten()
            );
        }

        return OfficeResource::collection($voucher->product->organization->offices);
    }
}
