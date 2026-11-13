<?php

namespace App\Modules\VoucherPass\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Modules\DCP\Data\DCPDeliveryAddress;
use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Data\GelrepasCardData;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Services\DCPOrderService;
use App\Modules\VoucherPass\Data\VoucherPassChange;
use App\Modules\VoucherPass\Data\VoucherPassDCPResult;
use App\Modules\VoucherPass\Enums\VoucherPassDCPStatus;
use App\Modules\VoucherPass\Models\VoucherPass;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

readonly class VoucherPassDCPService
{
    public const int BATCH_SIZE = 100;

    private const array MAILING_KEYS = [
        'mailing_street', 'mailing_house_number', 'mailing_house_letter', 'mailing_house_number_addition',
        'mailing_postal_code', 'mailing_city',
    ];

    private const array RECORD_KEYS = [
        'given_name', 'family_name', 'family_name_prefix', 'birth_date', 'municipality_code',
        'street', 'house_number', 'house_letter', 'house_number_addition', 'postal_code', 'city',
        ...self::MAILING_KEYS,
    ];

    /**
     * @param DCPOrderService $orders
     */
    public function __construct(private DCPOrderService $orders)
    {
    }

    /**
     * @param Organization $organization
     * @return int[]
     */
    public function candidateIds(Organization $organization): array
    {
        return $organization->voucher_passes()->where('print_requested', true)->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param Organization $organization
     * @param int[] $passIds
     * @param Employee|null $employee
     * @param bool $preview
     * @return VoucherPassDCPResult[]
     */
    public function prepareBatch(
        Organization $organization,
        array $passIds,
        ?Employee $employee = null,
        bool $preview = false,
    ): array {
        $results = [];
        $passes = $organization->voucher_passes()->whereKey($passIds)->orderBy('id')->get();

        foreach ($passes as $pass) {
            try {
                $results[] = $preview ? $this->preparePass($organization, $pass, $employee, true) :
                    DB::transaction(function () use ($organization, $pass, $employee): VoucherPassDCPResult {
                        $organization = Organization::query()->lockForUpdate()->findOrFail($organization->id);
                        $pass = $organization->voucher_passes()->lockForUpdate()->findOrFail($pass->id);

                        return $this->preparePass($organization, $pass, $employee, false);
                    });
            } catch (ValidationException $exception) {
                $results[] = new VoucherPassDCPResult(
                    $pass->id,
                    $pass->code,
                    null,
                    $preview ? VoucherPassDCPStatus::BLOCKED : VoucherPassDCPStatus::FAILED,
                    trans('voucher_pass::dcp.invalid_data'),
                    errors: Arr::flatten($exception->errors()),
                );
            } catch (Throwable $exception) {
                $diagnosticId = resolve(VoucherPassService::class)->logFailure(
                    $exception,
                    $organization,
                    $employee,
                    $preview ? 'dcp_preview' : 'dcp_prepare',
                    passId: $pass->id,
                );

                $results[] = new VoucherPassDCPResult(
                    $pass->id,
                    $pass->code,
                    null,
                    $preview ? VoucherPassDCPStatus::BLOCKED : VoucherPassDCPStatus::FAILED,
                    trans('voucher_pass::dcp.failed'),
                    diagnosticId: $diagnosticId,
                );
            }
        }

        return $results;
    }

    /**
     * @param Organization $organization
     * @param VoucherPass $pass
     * @param Employee|null $employee
     * @param bool $preview
     * @throws Throwable
     * @return VoucherPassDCPResult
     */
    private function preparePass(
        Organization $organization,
        VoucherPass $pass,
        ?Employee $employee,
        bool $preview,
    ): VoucherPassDCPResult {
        /** @var DCPOrder|null $order */
        $order = $pass->dcp_orders()->first();

        if ($order !== null && $order->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'order' => trans('dcp::validation.source_unavailable'),
            ]);
        }

        if (!$pass->print_requested || ($order !== null && !$order->canUpdateDraft())) {
            return new VoucherPassDCPResult(
                $pass->id,
                $pass->code,
                $order?->id,
                VoucherPassDCPStatus::SKIPPED,
                trans($pass->print_requested ? 'voucher_pass::dcp.frozen' : 'voucher_pass::dcp.print_not_requested'),
            );
        }

        if ($organization->dcp_connection_id === null ||
            ($order !== null && $order->dcp_connection_id !== $organization->dcp_connection_id)) {
            throw ValidationException::withMessages([
                'connection' => trans($organization->dcp_connection_id === null ?
                    'dcp::validation.connection_missing' : 'dcp::validation.connection_changed'),
            ]);
        }

        $data = $this->buildOrderData($pass, lock: !$preview);
        $attributes = $order ? $this->orders->changedDraftAttributes($order, $data) : $data->toAttributes();
        $previousValues = $order ? Arr::dot($order->only(array_keys($attributes))) : [];
        $values = Arr::dot($attributes);
        $changes = [];

        foreach (array_unique([...array_keys($values), ...array_keys($previousValues)]) as $key) {
            if (in_array($key, ['order_rows.0.key', 'order_rows.0.item', 'order_rows.0.tagIds.0.key'], true)) {
                continue;
            }

            $value = $values[$key] ?? null;

            if (($previousValues[$key] ?? null) !== $value) {
                $changes[] = new VoucherPassChange($key, $previousValues[$key] ?? null, $value);
            }
        }

        if ($preview) {
            $status = $order === null ? VoucherPassDCPStatus::WOULD_CREATE :
                ($attributes === [] ? VoucherPassDCPStatus::UNCHANGED : VoucherPassDCPStatus::WOULD_UPDATE);
        } else {
            $status = $order === null ? VoucherPassDCPStatus::CREATED :
                ($attributes === [] ? VoucherPassDCPStatus::UNCHANGED : VoucherPassDCPStatus::UPDATED);

            $order = $this->orders->createOrUpdateDraft($organization, $pass, $data, $employee);
        }

        return new VoucherPassDCPResult(
            $pass->id,
            $pass->code,
            $order?->id,
            $status,
            trans('voucher_pass::dcp.results.' . $status->value),
            $changes,
        );
    }

    /**
     * @param VoucherPass $pass
     * @param bool $lock
     * @throws ValidationException
     * @throws JsonException
     * @return DCPOrderData
     */
    private function buildOrderData(VoucherPass $pass, bool $lock): DCPOrderData
    {
        $query = Profile::query()
            ->where('organization_id', $pass->organization_id)
            ->whereIn('identity_id', [$pass->owner_identity_id, $pass->target_identity_id])
            ->orderBy('id');

        $profiles = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('identity_id');
        $owner = $profiles->get($pass->owner_identity_id);
        $recipient = $profiles->get($pass->target_identity_id);

        if ($owner === null) {
            throw ValidationException::withMessages([
                'owner' => trans('voucher_pass::dcp.owner_profile_missing'),
            ]);
        }

        if ($recipient === null) {
            throw ValidationException::withMessages([
                'profile' => trans('voucher_pass::dcp.profile_missing'),
            ]);
        }

        $ownerValues = $this->profileValues($owner, $lock);
        $recipientValues = $owner->id === $recipient->id ? $ownerValues : $this->profileValues($recipient, $lock);

        $mailing = collect(self::MAILING_KEYS)->contains(function (string $key) use ($recipientValues) {
            return filled($recipientValues[$key] ?? null);
        });

        $prefix = $mailing ? 'mailing_' : '';

        $rules = [
            'owner.given_name' => ['required', 'string'],
            'owner.family_name' => ['required', 'string'],
            'owner.birth_date' => ['required', 'date_format:Y-m-d'],
            'owner.municipality_code' => ['required', 'string'],
            'recipient.given_name' => ['required', 'string'],
            'recipient.family_name' => ['required', 'string'],
        ];

        foreach (['street', 'house_number', 'postal_code', 'city'] as $key) {
            $rules['recipient.' . $prefix . $key] = ['required', 'string'];
        }

        Validator::make([
            'owner' => $ownerValues,
            'recipient' => $recipientValues,
        ], $rules, attributes: trans('voucher_pass::dcp.fields'))->validate();

        $ownerLastName = trim(($ownerValues['family_name_prefix'] ?? '') . ' ' . $ownerValues['family_name']);
        $recipientLastName = trim(($recipientValues['family_name_prefix'] ?? '') . ' ' . $recipientValues['family_name']);

        $address = implode(' ', array_filter([
            $recipientValues[$prefix . 'street'],
            $recipientValues[$prefix . 'house_number'],
            $recipientValues[$prefix . 'house_letter'] ?? '',
            $recipientValues[$prefix . 'house_number_addition'] ?? '',
        ], fn (string $value) => $value !== ''));

        $data = new DCPOrderData(
            new DCPDeliveryAddress(
                $recipientValues['given_name'],
                $recipientLastName,
                $address,
                $recipientValues[$prefix . 'postal_code'],
                $recipientValues[$prefix . 'city'],
            ),
            new GelrepasCardData(
                $pass->print_label,
                $ownerValues['given_name'],
                $ownerLastName,
                CarbonImmutable::createFromFormat('!Y-m-d', $ownerValues['birth_date']),
                $ownerValues['municipality_code'],
                $pass->code,
            ),
        );

        $data = $data->normalizedForDraft();
        $data->validate();

        return $data;
    }

    /**
     * @param Profile $profile
     * @param bool $lock
     * @return array<string, string>
     */
    private function profileValues(Profile $profile, bool $lock): array
    {
        $query = $profile->profile_records()->whereNull('deleted_at')
            ->whereHas('record_type', function (Builder $query) {
                $query->whereIn('key', self::RECORD_KEYS);
            })->with('record_type');

        $records = ($lock ? $query->lockForUpdate() : $query)->get()->unique('record_type.key');

        return $records->mapWithKeys(fn (ProfileRecord $record) => [
            $record->record_type->key => trim($record->value),
        ])->all();
    }
}
