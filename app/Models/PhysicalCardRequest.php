<?php

namespace App\Models;

use App\Services\DCPService\Contracts\DCPOrderable;
use App\Services\DCPService\DCPApi;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Throwable;

/**
 * App\Models\PhysicalCardRequest.
 *
 * @property int $id
 * @property int|null $voucher_id
 * @property int|null $fund_request_id
 * @property int|null $physical_card_type_id
 * @property int|null $employee_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $address
 * @property string $house
 * @property string|null $house_addition
 * @property string $postcode
 * @property string $city
 * @property string|null $card_code
 * @property int|null $physical_card_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read DCPOrder|null $dcp_order
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\FundRequest|null $fund_request
 * @property-read \App\Models\PhysicalCard|null $physical_card
 * @property-read \App\Models\PhysicalCardType|null $physical_card_type
 * @property-read \App\Models\Voucher|null $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereHouseAddition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest wherePhysicalCardTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardRequest whereVoucherId($value)
 * @mixin \Eloquent
 */
class PhysicalCardRequest extends Model implements DCPOrderable
{
    /**
     * Fields which can be sent in the tag fields of the DCP order (see DCPConnection).
     */
    public const array DCP_SOURCE_FIELDS = [
        'recipient.first_name',
        'recipient.last_name',
        'recipient.full_name',
        'recipient.birth_date',
        'card.code',
        'card.number',
        'card_type.name',
        'voucher.number',
        'voucher.address',
        'voucher.qr_code',
        'fund.name',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'address', 'house', 'house_addition', 'postcode', 'city', 'employee_id', 'physical_card_type_id',
        'first_name', 'last_name', 'card_code', 'physical_card_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_request(): BelongsTo
    {
        return $this->belongsTo(FundRequest::class);
    }

    /**
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function dcp_order(): MorphOne
    {
        return $this->morphOne(DCPOrder::class, 'orderable');
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function physical_card(): BelongsTo
    {
        return $this->belongsTo(PhysicalCard::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function physical_card_type(): BelongsTo
    {
        return $this->belongsTo(PhysicalCardType::class);
    }

    /**
     * @return array
     */
    public function recipientData(): array
    {
        $identity = $this->voucher?->identity ?: $this->fund_request?->identity;

        $birthDate = $identity?->records()
            ->whereRelation('record_type', 'key', 'birth_date')
            ->where('value', '!=', '')
            ->orderByDesc('id')
            ->value('value');

        return [
            'first_name' => trim((string) $this->first_name) ?: null,
            'last_name' => trim((string) $this->last_name) ?: null,
            'birth_date' => $birthDate ?: null,
        ];
    }

    /**
     * @param Voucher|null $voucher
     * @return DCPOrder|null
     */
    public function makeDCPOrder(?Voucher $voucher = null): ?DCPOrder
    {
        $voucher = $this->voucher ?: $voucher;
        $cardType = $this->physical_card_type;
        $fund = $voucher?->fund;
        $connection = $fund?->getImplementation()->dcp_connection;

        if (
            !DCPOrder::isEnabled() ||
            !$cardType?->send_to_dcp ||
            !$fund?->organization ||
            !$connection?->acceptsOrderable($this) ||
            $this->dcp_order()->exists()
        ) {
            return null;
        }

        try {
            $this->voucher()->associate($voucher);
            $this->card_code = $this->card_code ?: $cardType->makeUniqueCode();
            $this->save();

            return DCPOrder::makeOrder($this, $fund->organization, $connection, $this->employee);
        } catch (Throwable $e) {
            DCPApi::logError("Physical card request [$this->id]: could not make the DCP order.", $e);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    public static function dcpOrderSourceFields(): array
    {
        return array_combine(self::DCP_SOURCE_FIELDS, array_map(
            fn (string $field) => trans("dcp.source_fields.physical_card_request.$field"),
            self::DCP_SOURCE_FIELDS,
        ));
    }

    /**
     * @return array<string, string|null>
     */
    public function dcpOrderSourceValues(): array
    {
        $cardType = $this->physical_card_type;
        $recipient = $this->recipientData();
        $voucherAddress = $this->voucher?->token_without_confirmation?->address;

        return [
            'recipient.first_name' => $recipient['first_name'],
            'recipient.last_name' => $recipient['last_name'],
            'recipient.full_name' => trim(implode(' ', array_filter([
                $recipient['first_name'],
                $recipient['last_name'],
            ]))) ?: null,
            'recipient.birth_date' => $recipient['birth_date'],
            'card.code' => $this->card_code,
            'card.number' => $this->card_code ? implode(' ', str_split(
                $this->card_code,
                max((int) $cardType?->code_block_size, 1),
            )) : null,
            'card_type.name' => $cardType?->name,
            'voucher.number' => $this->voucher?->number,
            'voucher.address' => $voucherAddress,
            'voucher.qr_code' => $voucherAddress ? json_encode([
                'type' => 'voucher',
                'value' => $voucherAddress,
            ]) : null,
            'fund.name' => $this->voucher?->fund?->name,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function dcpOrderDeliveryAddress(): array
    {
        $recipient = $this->recipientData();

        return [
            'firstname' => $recipient['first_name'],
            'lastname' => $recipient['last_name'],
            'address1' => implode(' ', array_filter([$this->address, $this->house, $this->house_addition])),
            'zipcode' => $this->postcode,
            'city' => $this->city,
        ];
    }

    /**
     * @return string|null
     */
    public function dcpOrderBlockReason(): ?string
    {
        return match (true) {
            !$this->physical_card_type?->send_to_dcp => trans('dcp.errors.card_type_not_sent'),
            !$this->voucher => trans('dcp.errors.voucher_missing'),
            $this->voucher->isDeactivated() => trans('dcp.errors.voucher_deactivated'),
            $this->voucher->expired => trans('dcp.errors.voucher_expired'),
            default => null,
        };
    }

    /**
     * @param DCPOrder $order
     * @return void
     */
    public function onDCPOrderSent(DCPOrder $order): void
    {
        if ($this->physical_card_id || !$this->card_code || !$this->voucher) {
            return;
        }

        $this->update([
            'physical_card_id' => $this->voucher->addPhysicalCard($this->card_code, $this->physical_card_type)->id,
        ]);
    }

    /**
     * @return array{name: string, voucher_id: int|null, voucher_number: string|null}
     */
    public function dcpOrderableDetails(): array
    {
        return [
            'name' => trim(implode(' ', [
                trans('dcp.orderable_types.physical_card_request'),
                "#$this->id",
                $this->physical_card_type?->name ? "({$this->physical_card_type->name})" : '',
            ])),
            'voucher_id' => $this->voucher?->id,
            'voucher_number' => $this->voucher?->number,
        ];
    }
}
