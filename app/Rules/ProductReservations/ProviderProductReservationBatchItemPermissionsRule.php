<?php

namespace App\Rules\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Class ProviderProductReservationBatchItemRule
 * @package App\Rules
 */
class ProviderProductReservationBatchItemPermissionsRule extends BaseRule
{
    protected $index;
    protected $request;
    protected $organization;
    protected $reservationsData;

    /**
     * ProviderProductReservationBatchItemRule constructor.
     * @param Organization $organization
     * @param array $reservationsData
     */
    public function __construct(
        Organization $organization,
        array $reservationsData = []
    ) {
        $this->request = BaseFormRequest::createFromBase(request());
        $this->organization = $organization;
        $this->reservationsData = $reservationsData;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // get current reservation index
        $this->index = (array_last(explode('.', $attribute)) ?? 0);

        /** @var Voucher|null $voucher current row voucher */
        /** @var Product|null $product current row product */
        $product = $this->reservationsData[$this->index]['product'] ?? null;
        $voucher = $this->reservationsData[$this->index]['voucher'] ?? null;
        $note = $this->reservationsData[$this->index]['note'] ?? '';

        // note has to be string
        if (!is_string($note)) {
            return $this->reject("Notitieveld mag alleen tekst bevatten.");
        }

        // product existence
        if (!$product) {
            return $this->reject("Aanbod niet gevonden.");
        }

        // product existence
        if (!$voucher) {
            return $this->reject("Tegoed niet gevonden.");
        }

        // validate voucher and provider organization
        if (($voucherAvailable = $this->validateVoucherAccess($voucher)) !== true) {
            return $this->reject(is_string($voucherAvailable) ? $voucherAvailable : trans('invalid_provider'));
        }

        // validate product access
        if (($productAvailable = $this->validateProductAccess($voucher, $product)) !== true) {
            return $this->reject(is_string($productAvailable) ? $productAvailable : trans('invalid_product'));
        }

        return true;
    }

    /**
     * @param Voucher $voucher
     * @return bool|string|null
     */
    protected function validateVoucherAccess(Voucher $voucher)
    {
        // only regular vouchers can be used for reservations
        if (!$voucher->isBudgetType()) {
            return "Dit tegoed heeft geen budget.";
        }

        $sponsorIsValid = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::whereId($this->organization->id),
            $this->request->auth_address(),
            $voucher
        )->exists();

        if ($sponsorIsValid && $inspection = Gate::inspect('useAsProvider', $voucher)) {
            return $inspection->allowed() ? true : $inspection->message();
        }

        return false;
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return bool|string
     */
    protected function validateProductAccess(Voucher $voucher, Product $product)
    {
        // The provider didn't enabled subsidy products reservation
        if ($voucher->fund->isTypeSubsidy() && !$product['reservations_subsidy_enabled']) {
            return 'U mag geen reserveringen plaatsen voor actie fondsen.';
        }

        // The provider didn't enabled budget products reservation
        if ($voucher->fund->isTypeBudget() && !$product['reservations_budget_enabled']) {
            return 'U mag geen reserveringen plaatsen voor budget fondsen.';
        }

        // product belongs to another organization
        if ($product->organization_id !== $this->organization->id) {
            return 'Dit aanbod is niet geplaatst door uw organisatie.';
        }

        // product sold out
        if ($product->sold_out) {
            return 'Niet genoeg voorraad voor het aanbod. Het aanbod kan verhoogd worden in de beheeromgeving.';
        }

        $allowed = false;
        $productQuery = Product::query()
            ->whereId($product->id)
            ->whereOrganizationId($this->organization->id);

        if ($voucher->fund->isTypeBudget()) {
            $allowed = ProductQuery::approvedForFundsFilter($productQuery, $voucher->fund_id)->exists();
        } elseif ($voucher->fund->isTypeSubsidy()) {
            $allowed = $productQuery->whereHas('fund_provider_products', function(Builder $builder) use ($voucher) {
                FundProviderProductQuery::whereAvailableForSubsidyVoucherFilter(
                    $builder,
                    $voucher,
                    $this->organization->id,
                    false
                );
            })->exists();
        }

        // This product was not approved for this found.
        return $allowed ?: "Dit aanbod is niet geaccepteerd voor dit fonds.";
    }

    public function reject($message): bool
    {
        return parent::reject($message);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return sprintf('Rij: %s: %s', $this->index + 1, ($this->messageText ?: ''));
    }
}
