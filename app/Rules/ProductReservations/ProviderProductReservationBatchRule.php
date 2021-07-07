<?php

namespace App\Rules\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\ProductSubQuery;

/**
 * Class ProviderProductReservationBatchRule
 * @package App\Rules
 */
class ProviderProductReservationBatchRule extends BaseRule
{
    protected $request;
    protected $reservationsData = null;

    /**
     * ProviderProductReservationBatchRule constructor.
     */
    public function __construct()
    {
        $this->request = BaseFormRequest::createFromBase(request());
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
        // check for required fields
        if (!$this->validateRequiredFields($value, ['number', 'product_id'])) {
            return $this->reject('Er ontbreekt een verplichte kolom of kolommen.');
        }

        return true;
    }

    /**
     * @param array $reservations
     * @param array $requiredFields
     * @return bool
     */
    public function validateRequiredFields(array $reservations = [], $requiredFields = []): bool
    {
        $countAll = count($reservations);
        $fieldsData = array_combine($requiredFields, array_fill(0, count($requiredFields), []));

        foreach ($requiredFields as $field) {
            $fieldsData[$field] = array_filter(array_pluck($reservations, $field));
        }

        return count(array_filter($fieldsData, function($fieldData) use ($countAll) {
            return count($fieldData) !== $countAll;
        })) === 0;
    }

    /**
     * Load request models (optimized by grouping)
     *
     * @param array $reservations
     * @return array
     */
    public function inflateReservationsData($reservations = []): array
    {
        $data = collect($reservations)->map(function($reservation) {
            $voucher = Voucher::findByPhysicalCard($reservation['number']);
            $voucher_id = $voucher ? $voucher->id : null;

            return array_merge(compact('voucher', 'voucher_id'), $reservation);
        });

        $groupedData = $data->filter(function($row) {
            return !is_null($row['voucher_id'] ?? null);
        })->groupBy('voucher_id');

        $groupedData = $groupedData->map(function($data, $voucher_id) {
            $productIds = array_pluck($data, 'product_id');
            $productsList = ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher_id
            ], Product::query()->whereIn('id', $productIds))->get();

            return $productsList->groupBy('id');
        });

        return $data->map(function($item) use ($groupedData) {
            return array_merge($item, [
                'product' => $groupedData[$item['voucher_id']][$item['product_id']][0] ?? null,
                'is_valid' => null,
            ]);
        })->toArray();
    }

    /**
     * @return array|null
     */
    public function getReservationsData(): ?array
    {
        return $this->reservationsData;
    }
}
