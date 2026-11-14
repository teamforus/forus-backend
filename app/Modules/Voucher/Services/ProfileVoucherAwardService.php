<?php

namespace App\Modules\Voucher\Services;

use App\Helpers\Arr;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\FundFormulaProduct;
use App\Models\FundLimitMultiplier;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Models\Voucher;
use App\Modules\Voucher\Data\ProfileVoucherAwardResult;
use App\Modules\Voucher\Data\ProfileVoucherPreviewResult;
use App\Rules\Vouchers\VoucherBatchRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;
use Throwable;

class ProfileVoucherAwardService
{
    public const int BATCH_SIZE = 100;

    private const array LOG_CONTEXT_KEYS = ['organization_id', 'employee_id', 'fund_id', 'profile_id', 'identity_id'];

    /**
     * @param Organization $organization
     * @return array<int, int>
     */
    public function candidateIds(Organization $organization): array
    {
        return $this->organizationProfilesQuery($organization)->pluck('id')->all();
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array<int, int> $fundIds
     * @param array<int, int>|null $profileIds
     * @throws Throwable
     * @return array<int, ProfileVoucherAwardResult>
     */
    public function makeVouchersFromProfiles(
        Organization $organization,
        Employee $employee,
        array $fundIds,
        ?array $profileIds = null,
    ): array {
        return $this->processProfiles($organization, $employee, $fundIds, preview: false, profileIds: $profileIds);
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array<int, int> $fundIds
     * @param array<int, int>|null $profileIds
     * @return array<int, ProfileVoucherPreviewResult>
     */
    public function previewVouchersFromProfiles(
        Organization $organization,
        Employee $employee,
        array $fundIds,
        ?array $profileIds = null,
    ): array {
        return $this->processProfiles($organization, $employee, $fundIds, preview: true, profileIds: $profileIds);
    }

    /**
     * @param Fund $fund
     * @param Profile $profile
     * @param Employee $employee
     * @throws Throwable
     * @return array{
     *     status: string, code?: string, message?: string|null,
     *     errors?: array<int, array{record_type_key: string|null, code: string}>,
     *     vouchers?: array<int, Voucher>,
     * }
     */
    public function makeVouchersFromProfile(Fund $fund, Profile $profile, Employee $employee): array
    {
        return $profile->identity->transactionWithFundAwardLock(function (Identity $identity) use (
            $fund,
            $profile,
            $employee,
        ): array {
            $fund = Fund::query()->findOrFail($fund->id);
            $profile = Profile::query()->lockForUpdate()->findOrFail($profile->id);
            $profile_records = $profile->profile_records()->with('record_type')->lockForUpdate()->get();

            $profile->setRelation('profile_records', $profile_records);
            $profile->setRelation('identity', $identity);

            $award = $this->evaluateProfileAward($fund, $profile, $employee);

            if ($award['status'] === 'skipped') {
                return $award;
            }

            $vouchers = [];
            $voucherFields = ['employee_id' => $employee->id];

            if ($award['amount'] !== null) {
                // Zero main-voucher suppression was already evaluated using profile values.
                $vouchers[] = $fund->makeVoucher(
                    identity: $identity,
                    voucherFields: $voucherFields,
                    amount: (string) $award['amount'],
                    limitMultiplier: $award['limit_multiplier'],
                    isMainVoucher: false,
                );
            }

            foreach ($award['multiple_vouchers'] as $multipleVoucher) {
                for ($index = 0; $index < $multipleVoucher['quantity']; $index++) {
                    $vouchers[] = $fund->makeVoucher(
                        identity: $identity,
                        voucherFields: $voucherFields,
                        amount: (string) $multipleVoucher['amount'],
                        limitMultiplier: 1,
                        isMainVoucher: false,
                    );
                }
            }

            $formulaProducts = $fund->fund_formula_products->keyBy('id');

            foreach ($award['products'] as $productAward) {
                if ($productAward['quantity'] === 0) {
                    continue;
                }

                /** @var Product $product */
                $product = $formulaProducts->get($productAward['formula_id'])->product;
                $expireAt = $product->expire_at && $fund->end_date->gt($product->expire_at) ?
                    $product->expire_at : $fund->end_date;

                for ($index = 0; $index < $productAward['quantity']; $index++) {
                    $vouchers[] = $fund->makeProductVoucher(
                        identity: $identity,
                        voucherFields: [...$voucherFields, 'amount' => $productAward['amount']],
                        productId: $productAward['product_id'],
                        expireAt: $expireAt,
                        price: $productAward['amount'],
                        dispatchCreated: false,
                    )->dispatchCreated(notifyRequesterReserved: false, notifyProviderReserved: false);
                }
            }

            return [
                'status' => 'created',
                'vouchers' => $vouchers,
            ];
        });
    }

    /**
     * @param Organization $organization
     * @return HasMany<Profile, Organization>
     */
    private function organizationProfilesQuery(Organization $organization): HasMany
    {
        return $organization->profiles()
            ->whereRelation('requester_import_rows', 'organization_id', $organization->id)
            ->orderBy('id');
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array<int, int> $fundIds
     * @param bool $preview
     * @param array<int, int>|null $profileIds
     * @return ($preview is true ? array<int, ProfileVoucherPreviewResult> : array<int, ProfileVoucherAwardResult>)
     */
    private function processProfiles(
        Organization $organization,
        Employee $employee,
        array $fundIds,
        bool $preview,
        ?array $profileIds,
    ): array {
        $funds = $organization->funds()->whereIn('id', $fundIds)->orderBy('id')->get();

        $query = $this->organizationProfilesQuery($organization);

        if ($profileIds !== null) {
            $query->whereKey($profileIds);
        }

        $profiles = $query->with('identity:id')->get(['id', 'identity_id']);
        $profileRecords = ProfileRecord::query()
            ->whereIn('profile_id', $profiles->modelKeys())
            ->whereNull('deleted_at')
            ->whereHas('record_type', function (Builder $query) {
                $query->whereIn('key', ['given_name', 'family_name', 'client_number']);
            })
            ->with('record_type')
            ->latest()->latest('id')
            ->get()
            ->groupBy('profile_id');

        $operation = $preview ? 'preview_from_profile' : 'award_from_profile';
        $results = [];

        foreach ($funds as $fund) {
            foreach ($profiles as $profile) {
                $values = $profileRecords->get($profile->id, collect())
                    ->unique('record_type_id')
                    ->mapWithKeys(fn (ProfileRecord $record) => [$record->record_type->key => $record->value]);

                $profileName = collect([$values->get('given_name'), $values->get('family_name')])
                    ->filter(fn (?string $value) => filled($value))->implode(' ');

                $context = [
                    'fund_id' => $fund->id,
                    'fund_name' => $fund->name,
                    'profile_id' => $profile->id,
                    'identity_id' => $profile->identity_id,
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                ];

                try {
                    $result = $preview ?
                        $this->previewVouchersFromProfile($fund, $profile, $employee) :
                        $this->makeVouchersFromProfile($fund, $profile, $employee);

                    $formulaErrors = $result['errors'] ?? [];

                    $status = $result['status'];
                    $message = match (true) {
                        $status === 'created' => trans('fund.profile_award_created'),
                        $status === 'eligible' => trans('fund.profile_award_preview_eligible'),
                        $formulaErrors !== [] => trans('fund.profile_award_calculation_failed'),
                        default => $result['message'] ?: trans('fund.profile_award_not_eligible'),
                    };

                    $errors = $formulaErrors ? $this->formatFormulaErrors($formulaErrors, $organization->id) : [];
                    $diagnosticId = $formulaErrors ?
                        $this->logFailure($context, formulaErrors: $formulaErrors, operation: $operation) : null;

                    $voucherCount = $result['voucher_count'] ?? 0;
                    $voucherIds = array_map(fn (Voucher $voucher) => $voucher->id, $result['vouchers'] ?? []);
                } catch (Throwable $exception) {
                    $status = 'failed';
                    $message = match (true) {
                        $exception instanceof ValidationException => trans('validation.header'),
                        $preview => trans('fund.profile_award_preview_failed'),
                        default => trans('fund.profile_award_failed'),
                    };

                    $errors = $exception instanceof ValidationException ? Arr::flatten($exception->errors()) : [];
                    $diagnosticId = $this->logFailure($context, $exception, operation: $operation);
                    $voucherCount = 0;
                    $voucherIds = [];
                }

                if ($preview) {
                    $results[] = new ProfileVoucherPreviewResult(
                        fundId: $fund->id,
                        fundName: $fund->name,
                        profileId: $profile->id,
                        identityId: $profile->identity_id,
                        profileName: $profileName ?: null,
                        clientNumber: $values->get('client_number'),
                        status: $status,
                        message: $message,
                        errors: $errors,
                        diagnosticId: $diagnosticId,
                        voucherCount: $voucherCount,
                    );

                    continue;
                }

                $results[] = new ProfileVoucherAwardResult(
                    fundId: $fund->id,
                    fundName: $fund->name,
                    profileId: $profile->id,
                    identityId: $profile->identity_id,
                    profileName: $profileName ?: null,
                    clientNumber: $values->get('client_number'),
                    status: $status,
                    message: $message,
                    errors: $errors,
                    diagnosticId: $diagnosticId,
                    voucherIds: $voucherIds,
                );
            }
        }

        return $results;
    }

    /**
     * @param Fund $fund
     * @param Profile $profile
     * @param Employee $employee
     * @return array<string, mixed>
     */
    private function previewVouchersFromProfile(Fund $fund, Profile $profile, Employee $employee): array
    {
        $fund = Fund::query()->findOrFail($fund->id);
        $profile = Profile::query()->with(['identity', 'profile_records.record_type'])->findOrFail($profile->id);
        $award = $this->evaluateProfileAward($fund, $profile, $employee);

        if ($award['status'] === 'skipped') {
            return $award;
        }

        return [
            'status' => 'eligible',
            'voucher_count' => ($award['amount'] !== null ? 1 : 0) +
                array_sum(array_column($award['multiple_vouchers'], 'quantity')) +
                array_sum(array_column($award['products'], 'quantity')),
        ];
    }

    /**
     * @param Fund $fund
     * @param Profile $profile
     * @param Employee $employee
     * @return array<string, mixed>
     */
    private function evaluateProfileAward(Fund $fund, Profile $profile, Employee $employee): array
    {
        abort_unless($employee->organization_id === $fund->organization_id, 403);

        $eligibility = Gate::forUser($profile->identity)->inspect('applyFromProfile', [
            $fund, $profile, 'profile_award@' . $profile->id,
        ]);

        if ($eligibility->denied()) {
            return [
                'status' => 'skipped',
                'code' => 'not_eligible',
                'message' => $eligibility->message(),
            ];
        }

        $award = $this->calculateProfileAward($fund, $profile);

        if ($award['status'] !== 'skipped') {
            $this->validateProfileAward($fund, $award)->validate();
        }

        return $award;
    }

    /**
     * @param Fund $fund
     * @param Profile $profile
     * @return array{
     *     status: string, amount?: float|null, limit_multiplier?: int,
     *     multiple_vouchers?: array<int, array{amount: float, quantity: int}>,
     *     products?: array<int, array{formula_id: int, product_id: int, amount: float, quantity: int}>,
     *     errors?: array<int, array{record_type_key: string|null, code: string}>,
     * }
     */
    private function calculateProfileAward(Fund $fund, Profile $profile): array
    {
        $fund->loadMissing('fund_formulas', 'fund_formula_products.product', 'fund_limit_multipliers');

        $values = $profile->getCurrentRecordsForVoucherAward()
            ->map(fn (ProfileRecord $record) => $record->value)->all();

        $formulas = $fund->matchingBudgetFormulas($values);

        $multipleVouchers = $formulas->where('type', FundFormula::TYPE_MULTIPLY_VOUCHERS)
            ->map(fn (FundFormula $formula) => [
                'amount' => (float) $formula->amount,
                'quantity' => $fund->getFormulaMultipleVouchersCount($formula, $profile->identity, $values),
            ])->filter(fn (array $voucher) => $voucher['quantity'] > 0)->values();

        $amount = $formulas->isNotEmpty() ? $fund->amountForRecordValues($values) : null;

        $multipleVouchersAmount = $multipleVouchers->sum(fn (array $voucher) =>
            $voucher['amount'] * $voucher['quantity']);

        if ($amount === 0.0 && $multipleVouchersAmount > 0) {
            $amount = null;
        }

        if ($errors = $this->validateProfileAwardInputs($fund, $values, hasBudgetVoucher: $amount !== null)) {
            return [
                'status' => 'skipped',
                'errors' => $errors,
            ];
        }

        $limitMultiplier = 1;

        if ($amount !== null && $fund->fund_limit_multipliers->isNotEmpty()) {
            $limitMultiplier = $fund->fund_limit_multipliers->sum(function (
                FundLimitMultiplier $multiplier,
            ) use ($values) {
                return (int) ($values[$multiplier->record_type_key] ?? 1) * $multiplier->multiplier;
            });
        }

        $products = $fund->matchingProductFormulas($values)->map(fn (FundFormulaProduct $formula) => [
            'formula_id' => $formula->id,
            'product_id' => $formula->product_id,
            'amount' => (float) ($formula->price ?: $formula->product?->price),
            'quantity' => $formula->record_type_key_multiplier === null ? 1 :
                (int) $values[$formula->record_type_key_multiplier],
        ])->all();

        if ($amount === null && $multipleVouchers->isEmpty() && $products === []) {
            return [
                'status' => 'skipped',
                'message' => trans('fund.profile_award_no_matching_formulas'),
            ];
        }

        return [
            'status' => 'calculated',
            'amount' => $amount,
            'multiple_vouchers' => $multipleVouchers->all(),
            'products' => $products,
            'limit_multiplier' => $limitMultiplier,
        ];
    }

    /**
     * @param Fund $fund
     * @param array{
     *     status: string, amount: float|null, limit_multiplier: int,
     *     multiple_vouchers: array<int, array{amount: float, quantity: int}>,
     *     products: array<int, array{formula_id: int, product_id: int, amount: float, quantity: int}>,
     * } $award
     * @return ValidationValidator
     */
    private function validateProfileAward(Fund $fund, array $award): ValidationValidator
    {
        $vouchers = [];

        if ($award['amount'] !== null) {
            $vouchers[] = [
                'amount' => $award['amount'],
                'limit_multiplier' => $award['limit_multiplier'],
                'quantity' => 1,
            ];
        }

        foreach ($award['multiple_vouchers'] as $multipleVoucher) {
            $vouchers[] = [
                ...$multipleVoucher,
                'limit_multiplier' => 1,
            ];
        }

        foreach ($award['products'] as $product) {
            if ($product['quantity'] === 0) {
                continue;
            }

            $vouchers[] = [
                'amount' => $product['amount'],
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
            ];
        }

        $amounts = array_map(fn (array $voucher) => [
            'amount' => $voucher['amount'] * $voucher['quantity'],
        ], $vouchers);

        $productCounts = collect($vouchers)
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn (SupportCollection $products) => $products->sum('quantity'))
            ->all();

        return Validator::make(compact('vouchers'), [
            'vouchers' => 'required|array',
            'vouchers.*.amount' => VoucherBatchRules::ruleAmount($fund, $amounts),
            'vouchers.*.product_id' => VoucherBatchRules::ruleProductId($fund, $productCounts),
            'vouchers.*.limit_multiplier' => VoucherBatchRules::ruleLimitMultiplier(),
            'vouchers.*.quantity' => 'required|integer|min:1',
        ]);
    }

    /**
     * @param Fund $fund
     * @param array<string, mixed> $values
     * @param bool $hasBudgetVoucher
     * @return array<int, array{record_type_key: string|null, code: string}>
     */
    private function validateProfileAwardInputs(Fund $fund, array $values, bool $hasBudgetVoucher): array
    {
        $fundFormulaRecordKeys = $fund->matchingBudgetFormulas($values)
            ->whereIn('type', [FundFormula::TYPE_MULTIPLY, FundFormula::TYPE_MULTIPLY_VOUCHERS])
            ->pluck('record_type_key');

        $fundLimitMultiplierRecordKeys = $hasBudgetVoucher ? $fund->fund_limit_multipliers->pluck('record_type_key')
            ->filter(fn (?string $key) => $key === null || isset($values[$key])) : [];

        $fundFormulaProductRecordKeys = $fund->matchingProductFormulas($values)->pluck('record_type_key_multiplier')
            ->filter(fn (?string $key) => $key !== null);

        $requiredKeys = $fundFormulaRecordKeys
            ->merge($fundLimitMultiplierRecordKeys)
            ->merge($fundFormulaProductRecordKeys)
            ->uniqueStrict()
            ->values();

        $errors = [];

        foreach ($requiredKeys as $key) {
            $value = $values[$key] ?? null;

            $code = match (true) {
                $key === null => 'formula_record_type_missing',
                !filled($value) => 'formula_input_missing',
                !is_numeric($value) => 'formula_input_not_numeric',
                default => null,
            };

            if ($code !== null) {
                $errors[] = ['record_type_key' => $key, 'code' => $code];
            }
        }

        return $errors;
    }

    /**
     * @param array<int, array{record_type_key: string|null, code: string}> $errors
     * @param int $organizationId
     * @return array<int, string>
     */
    private function formatFormulaErrors(array $errors, int $organizationId): array
    {
        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $organizationId,
            includeKeys: array_values(array_filter(
                array_column($errors, 'record_type_key'),
                fn (?string $key) => $key !== null,
            )),
        );

        return array_map(function (array $error) use ($recordTypes): string {
            $name = $recordTypes->get($error['record_type_key'])?->name ?: $error['record_type_key'];

            return match ($error['code']) {
                'formula_record_type_missing' => trans('fund.profile_award_errors.record_type_missing'),
                'formula_input_missing' => trans('fund.profile_award_errors.input_missing', ['name' => $name]),
                'formula_input_not_numeric' => trans('fund.profile_award_errors.input_not_numeric', ['name' => $name]),
            };
        }, $errors);
    }

    /**
     * @param array<string, mixed> $context
     * @param Throwable|null $exception
     * @param array<int, array{record_type_key: string|null, code: string}> $formulaErrors
     * @param string $operation
     * @return string
     */
    private function logFailure(
        array $context,
        ?Throwable $exception = null,
        array $formulaErrors = [],
        string $operation = 'award_from_profile',
    ): string {
        $diagnosticId = Str::uuid()->toString();
        $expected = $exception === null || $exception instanceof ValidationException;

        $errorCode = match (true) {
            $exception === null => 'formula_invalid',
            $exception instanceof ValidationException => 'validation_failed',
            default => 'award_failed',
        };

        try {
            Log::channel('voucher_awards')->log(
                $expected ? 'warning' : 'error',
                $expected ? 'Profile voucher award rejected.' : 'Unexpected profile voucher award failure.',
                array_filter([
                    ...Arr::only($context, self::LOG_CONTEXT_KEYS),
                    'diagnostic_id' => $diagnosticId,
                    'operation' => $operation,
                    'error_code' => $errorCode,
                    'formula_errors' => $formulaErrors,
                    'validation_fields' => $exception instanceof ValidationException ?
                        array_keys($exception->errors()) : null,
                    'exception_class' => $exception ? $exception::class : null,
                    'exception_file' => $exception?->getFile(),
                    'exception_line' => $exception?->getLine(),
                    'previous_exception_class' => $exception?->getPrevious() ? $exception->getPrevious()::class : null,
                ], fn (mixed $value) => $value !== null),
            );
        } catch (Throwable) {
            // Logging failures must not replace the original response.
        }

        return $diagnosticId;
    }
}
