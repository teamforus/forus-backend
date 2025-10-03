<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Services\EventLogService\Models\EventLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property Voucher $resource
 */
class VoucherResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'logs',
        'parent',
        'token_with_confirmation',
        'last_transaction',
        'transactions',
        'product_vouchers.fund',
        'product_vouchers.product.photo.presets',
        'product_vouchers.token_with_confirmation',
        'product_vouchers.product_reservation',
        'reimbursements_pending',
        'fund.fund_config.implementation',
        'physical_cards',
        'last_deactivation_log',
        'top_up_transactions',
        'voucher_records.record_type.translations',
    ];

    public const array LOAD_COUNT = [
        'transactions',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        $prepend = $append ? "$append." : '';

        return [
            ...parent::load($append),
            ...MediaResource::load("{$prepend}fund.logo"),
            ...MediaResource::load("{$prepend}product.photo"),
            ...OfficeResource::load("{$prepend}fund.provider_organizations_approved.offices"),
            ...OrganizationBasicResource::load("{$prepend}product.organization"),
            ...OrganizationBasicResource::load("{$prepend}fund.organization"),
            ...VoucherTransactionResource::load("{$prepend}all_transactions"),
            ...FundPhysicalCardTypeResource::load("{$prepend}fund.fund_physical_card_types"),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @throws Exception
     * @return array
     */
    public function toArray(Request $request): array
    {
        $voucher = $this->resource;
        $physicalCard = $voucher->physical_cards[0] ?? null;
        $deactivationDate = $voucher->deactivated ? $this->getDeactivationDate($voucher) : null;

        return [
            ...$voucher->only([
                'id', 'number', 'identity_id', 'fund_id', 'returnable', 'transactions_count',
                'expired', 'deactivated', 'type', 'state', 'state_locale', 'external',
            ]),
            ...$this->getBaseFields($voucher),
            ...$this->getOptionalFields($voucher),
            'deactivated_at' => $deactivationDate?->format('Y-m-d'),
            'deactivated_at_locale' => format_date_locale($deactivationDate),
            'history' => $this->getStateHistory($voucher),
            'expire_at' => [
                'date' => $voucher->expire_at->format('Y-m-d H:i:s.00000'),
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
            'timestamp' => $voucher->created_at->timestamp,
            'fund' => $this->getFundResource($voucher->fund),
            'parent' => $voucher->parent ? [
                ...$voucher->parent->only(['identity_id', 'fund_id']),
                'created_at' => $voucher->parent->created_at_string,
            ] : null,
            'physical_card' => new PhysicalCardResource($physicalCard),
            'product_vouchers' => $this->getProductVouchers($voucher->product_vouchers),
            'query_product' => $this->queryProduct($voucher, $request->get('product_id')),
            ...$this->getRecords($voucher),
            ...$this->timestamps($voucher, 'created_at'),
        ];
    }

    /**
     * @param Voucher $voucher
     * @param int|null $product_id
     * @throws Exception
     * @return array|null
     */
    public function queryProduct(Voucher $voucher, ?int $product_id = null): ?array
    {
        /** @var Product|null $product */
        $product = $product_id ? ProductSubQuery::appendReservationStats([
            'voucher_id' => $voucher->id,
        ], Product::whereId($product_id))->first() : null;

        if (!$product) {
            return null;
        }

        $expire_at = $voucher->calcExpireDateForProduct($product);
        $reservable_expire_at = $expire_at?->format('Y-m-d');
        $allow_reservations = $voucher->fund->fund_config->allow_reservations;
        $reservable_enabled = $allow_reservations && $product->reservationsEnabled();

        $has_amount = $voucher->amount_available >= $product->fundPrice($voucher->fund);
        $extra_payment_possible = $product->reservationExtraPaymentsEnabled($voucher->fund, $voucher->amount_available);

        $reservable_count = isset($product['limit_available']) && is_numeric($product['limit_available'])
            ? (int) $product['limit_available']
            : null;

        $reservable = (
            $reservable_count === null ||
            (FundQuery::whereProductsAreApprovedAndActiveFilter($voucher->fund, $product)->exists() && $reservable_count > 0)
        );

        return [
            'reservable' => $reservable_enabled && $reservable && ($has_amount || $extra_payment_possible),
            'reservable_count' => $reservable_count,
            'reservable_enabled' => $reservable_enabled,
            'reservable_expire_at' => $reservable_expire_at,
            'reservable_expire_at_locale' => format_date_locale($reservable_expire_at ?? null),
        ];
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

        return [
            'records' => VoucherRecordResource::collection($records),
            'records_title' => $voucher->getRecordsTitle(),
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
     * @return SupportCollection
     */
    protected function getStateHistory(Voucher $voucher): SupportCollection
    {
        $logs = $voucher->requesterHistoryLogs();

        return $logs->map(fn (EventLog $eventLog) => [
            ...$eventLog->only('id', 'event'),
            'event_locale' => $eventLog->eventDescriptionLocaleWebshop(),
            'created_at' => $eventLog->created_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale($eventLog->created_at),
        ])->values();
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getBaseFields(Voucher $voucher): array
    {
        if ($voucher->isBudgetType()) {
            $amount = $voucher->amount_available_cached;
            $used = $amount == 0;
            $productResource = null;
        } elseif ($voucher->type === 'product') {
            $used = $voucher->transactions_count > 0;
            $amount = $voucher->amount;

            $productResource = [
                ...$voucher->product->only([
                    'id', 'name', 'description', 'description_html', 'price', 'price_locale',
                    'total_amount', 'sold_amount', 'organization_id',
                ]),
                ...$voucher->product->translateColumns($voucher->product->only([
                    'name', 'description_html',
                ])),
                'price_locale' => $voucher->product->priceLocale(),
                'expire_at' => $voucher->product->expire_at ? $voucher->product->expire_at->format('Y-m-d') : '',
                'expire_at_locale' => format_datetime_locale($voucher->product->expire_at),
                'photo' => new MediaResource($voucher->product->photo),
                'organization' => new OrganizationBasicWithPrivateResource($voucher->product->organization),
            ];
        } else {
            abort('Unknown voucher type!', 403);
        }

        return [
            'used' => $used,
            'amount' => currency_format($amount),
            'amount_locale' => currency_format_locale($amount),
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
            'offices' => $this->getOffices($voucher),
            'transactions' => $this->getTransactions($voucher),
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getFundResource(Fund $fund): array
    {
        return  [
            ...$fund->only('id', 'state', 'type'),
            ...$fund->translateColumns($fund->only(['name'])),
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => new MediaCompactResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d H:i'),
            'start_date_locale' => format_datetime_locale($fund->start_date),
            'end_date' => $fund->end_date->format('Y-m-d H:i'),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationBasicWithPrivateResource($fund->organization),
            'allow_physical_cards' => $fund->fund_config->allow_physical_cards,
            'allow_blocking_vouchers' => $fund->fund_config->allow_blocking_vouchers,
            'fund_physical_card_types' => FundPhysicalCardTypeResource::collection($fund->fund_physical_card_types),
            ...$fund->fund_config->only(['allow_reimbursements', 'allow_reservations', 'key']),
        ];
    }

    /**
     * @param Collection|Voucher[]|null $product_vouchers
     * @return Voucher[]|SupportCollection|null
     */
    protected function getProductVouchers(
        Collection|array|null $product_vouchers
    ): SupportCollection|array|null {
        return $product_vouchers?->map(fn (Voucher $product_voucher) => [
            ...$product_voucher->only([
                'identity_id', 'fund_id', 'returnable',
            ]),
            'address' => $product_voucher->token_with_confirmation?->address,
            'amount' => currency_format($product_voucher->amount),
            'amount_locale' => currency_format_locale($product_voucher->amount),
            'date' => $product_voucher->created_at->format('M d, Y'),
            'date_time' => $product_voucher->created_at->format('M d, Y H:i'),
            'timestamp' => $product_voucher->created_at->timestamp,
            'product' => self::getProductDetails($product_voucher),
            'product_reservation' => $product_voucher->product_reservation?->only('id', 'code'),
            ...$this->timestamps($product_voucher, 'created_at'),
        ])->values();
    }

    /**
     * @param Voucher $product_voucher
     * @return array
     */
    protected static function getProductDetails(Voucher $product_voucher): array
    {
        return [
            ...$product_voucher->product->only([
                'id', 'name', 'description', 'total_amount',
                'sold_amount', 'product_category_id', 'organization_id',
            ]),
            'price' => currency_format($product_voucher->product->price),
            'price_locale' => $product_voucher->product->price_locale,
        ];
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getTransactions(Voucher $voucher): AnonymousResourceCollection
    {
        $transactions = BaseFormRequest::createFromBase(request())->isMeApp() ?
            $voucher->all_transactions->where('target', VoucherTransaction::TARGET_PROVIDER) :
            $voucher->all_transactions;

        return VoucherTransactionResource::collection($transactions);
    }

    /**
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     */
    protected function getOffices(Voucher $voucher): AnonymousResourceCollection
    {
        $offices = $voucher->isBudgetType() ?
            $voucher->fund->provider_organizations_approved->pluck('offices')->flatten() :
            $voucher->product->organization->offices;

        return OfficeResource::collection($offices);
    }
}
