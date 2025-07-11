<?php

namespace App\Rules\ProductReservations;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;

class ProviderProductReservationBatchItemStockRule extends BaseRule
{
    protected int $index;
    protected Organization $organization;
    protected array $reservationsData;

    /**
     * ProviderProductReservationBatchItemRule constructor.
     * @param Organization $organization
     * @param array $reservationsData
     */
    public function __construct(Organization $organization, array $reservationsData = [])
    {
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
        $this->index = (int) (array_last(explode('.', $attribute)) ?? 0);

        /** @var Voucher|null $voucher current row voucher */
        /** @var Product|null $product current row product */
        $product = $this->reservationsData[$this->index]['product'] ?? null;
        $voucher = $this->reservationsData[$this->index]['voucher'] ?? null;

        // get current and previous reservation from the list
        $currenRowNumber = array_search($this->index, array_keys($this->reservationsData)) + 1;
        $prevReservations = array_slice($this->reservationsData, 0, $currenRowNumber, true);

        $prevReservations = array_filter($prevReservations, function ($row) {
            return $row['is_valid'] !== false;
        });

        // check stock
        $allowed = $this->isValidProductStock($voucher, $product, $prevReservations);

        // when product is in stock check the amount on the voucher as well
        if ($allowed === true) {
            $allowed = $this->isValidProductAmount($voucher, $prevReservations);
        }

        $state = is_string($allowed) ? $this->reject($allowed) : $allowed;

        return $this->reservationsData[$this->index]['is_valid'] = $state;
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
     * @param Product $product
     * @param array $prevReservations
     * @return bool|string
     */
    protected function isValidProductStock(
        Voucher $voucher,
        Product $product,
        array $prevReservations = []
    ): bool|string {
        // total amount of reservations of the target product
        $target_products_count = count(array_filter($prevReservations, function ($reservation) use ($product) {
            return $reservation['product_id'] == $product->id;
        }));

        // total amount of reservations of the target product for the target voucher
        $target_voucher_products_count = count(array_filter($prevReservations, function ($reservation) use ($product, $voucher) {
            return $reservation['product_id'] == $product->id && $reservation['voucher_id'] == $voucher->id;
        }));

        // skip limits check budget products where limits are not set
        $skipTotalLimit = is_null($product['limit_total_available']);
        $skipVoucherLimit = is_null($product['limit_available']);

        // Sponsor total limit for the product reached.
        // Total limit of %s for the product "%s" reached!
        if (!$skipTotalLimit && $target_products_count > $product['limit_total_available']) {
            return sprintf('Het aanbod "%s" heeft het limiet bereikt!', $product['limit_total']);
        }

        // The total limit of %s for the voucher was reached!
        if (!$skipVoucherLimit && $target_voucher_products_count > $product['limit_available']) {
            return sprintf('Het aanbod "%s" heeft het limiet bereikt!', $product['limit_per_identity']);
        }

        return true;
    }

    /**
     * @param Voucher $voucher
     * @param array $prevReservations
     * @return bool|string
     */
    protected function isValidProductAmount(
        Voucher $voucher,
        array $prevReservations = []
    ): bool|string {
        // filter reservations by voucher
        $reservationsProducts = array_filter($prevReservations, function ($reservation) use ($voucher) {
            return $reservation['voucher_id'] == $voucher->id;
        });

        // total amount from the voucher
        $totalAmount = array_sum(array_pluck($reservationsProducts, 'product.amount'));

        // Sponsor total limit for the product reached.
        if ($totalAmount > $voucher->amount_available) {
            return 'Onvoldoende tegoed.';
        }

        return true;
    }
}
