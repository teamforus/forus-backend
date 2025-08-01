<?php

namespace App\Rules\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Support\Facades\Gate;

class ProviderProductReservationBatchItemPermissionsRule extends BaseRule
{
    protected int $index;
    protected BaseFormRequest $request;
    protected Organization $organization;
    protected array $reservationsData;

    /**
     * ProviderProductReservationBatchItemRule constructor.
     * @param Organization $organization
     * @param array $reservationsData
     */
    public function __construct(
        Organization $organization,
        array $reservationsData = []
    ) {
        $this->request = BaseFormRequest::createFrom(request());
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
            return $this->reject('Notitieveld mag alleen tekst bevatten.');
        }

        // product existence
        if (!$voucher) {
            return $this->reject('Tegoed niet gevonden.');
        }

        // product existence
        if (!$product) {
            return $this->reject('Aanbod niet gevonden.');
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
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return sprintf('Rij: %s: %s', $this->index + 1, ($this->messageText ?: ''));
    }

    /**
     * @param Voucher $voucher
     * @return bool|string|null
     */
    protected function validateVoucherAccess(Voucher $voucher): bool|string|null
    {
        // only regular vouchers can be used for reservations
        if (!$voucher->isBudgetType()) {
            return 'Dit tegoed heeft geen budget.';
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
    protected function validateProductAccess(Voucher $voucher, Product $product): bool|string
    {
        // The provider didn't enable product reservation
        if (!$product['reservations_enabled']) {
            return 'U mag geen reserveringen plaatsen voor fondsen.';
        }

        // The fund doesn't allow reservations
        if (!$voucher->fund->fund_config->allow_reservations) {
            return 'The fund is not allowing reservations';
        }

        // product belongs to another organization
        if ($product->organization_id !== $this->organization->id) {
            return 'Dit aanbod is niet geplaatst door uw organisatie.';
        }

        // product sold out
        if ($product->sold_out) {
            return 'Niet genoeg voorraad voor het aanbod. Het aanbod kan verhoogd worden in de beheeromgeving.';
        }

        $builder = Product::query();
        $builder = ProductQuery::whereCanBeReservedFilter($builder);
        $builder = ProductQuery::approvedForFundsAndActiveFilter($builder, $voucher->fund_id);

        // check validity
        $allowed = $builder
            ->where('organization_id', $this->organization->id)
            ->where('id', '=', $product->id)
            ->exists();

        // This product was not approved for this found.
        return $allowed ?: 'Dit aanbod is niet geaccepteerd voor dit fonds.';
    }
}
