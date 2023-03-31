<?php

namespace App\Rules\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\ProductSubQuery;
use Illuminate\Database\Eloquent\Builder;

class ProviderProductReservationBatchRule extends BaseRule
{
    protected BaseFormRequest $request;

    /**
     * ProviderProductReservationBatchRule constructor.
     */
    public function __construct()
    {
        $this->request = BaseFormRequest::createFrom(request());
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
    public function validateRequiredFields(array $reservations = [], array $requiredFields = []): bool
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
    public function inflateReservationsData(array $reservations = []): array
    {
        $data = collect($reservations)->map(function($reservation) {
            $voucher = Voucher::findByPhysicalCardQuery($reservation['number']);

            $voucher = $voucher->whereHas('fund', function (Builder $builder) {
                $builder->whereRelation('fund_config', 'allow_reservations', true);
            })->first();

            return array_merge([
                'voucher' => $voucher,
                'voucher_id' => $voucher?->id,
            ], $reservation);
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
}
