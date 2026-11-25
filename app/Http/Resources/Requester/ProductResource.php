<?php

namespace App\Http\Resources\Requester;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ReservationFieldResource;
use App\Http\Resources\Small\MediaSmallResource;
use App\Http\Resources\Small\OfficeSmallResource;
use App\Http\Resources\Traits\Products\ResolvesProductFeatures;
use App\Http\Resources\Traits\Products\ResolvesProductFunds;
use App\Models\Implementation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductResource extends \App\Http\Resources\ProductResource
{
    use ResolvesProductFeatures;
    use ResolvesProductFunds;

    public const array LOAD = [
        ...parent::LOAD,
        'organization.reservation_fields',
        'reservation_fields',
    ];

    public const array LOAD_NESTED = [
        ...parent::LOAD_NESTED,
        'photos' => MediaSmallResource::class,
        'organization.offices' => OfficeSmallResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $baseRequest = BaseFormRequest::createFrom($request);
        $funds = $product->trashed() ? collect() : $this->getProductFunds($baseRequest, $product);

        return [
            ...parent::toArray($request),
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
            ...$this->minimumPriceFields($product, $this->fundsQuery()),
            'funds' => $funds,
            ...$this->productFeatures($funds),
            'photos' => MediaSmallResource::collection($product->photos),
            'offices' => OfficeSmallResource::collection($product->organization->offices),
            'reservation' => $this->requesterReservationFields($product),
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Implementation::activeFundsQuery();
    }

    /**
     * @param Product $product
     * @return array
     */
    private function requesterReservationFields(Product $product): array
    {
        return [
            'phone' => $product->getReservationPhoneField(),
            'address' => $product->getReservationAddressField(),
            'birth_date' => $product->getReservationBirthDateField(),
            'note' => $product->organization->reservation_user_note,
            'fields' => $product->reservation_fields_enabled
                ? ReservationFieldResource::collection($product->getAvailableReservationFieldsForRequester())
                : [],
        ];
    }
}
