<?php

namespace App\Http\Resources\Requester;

use App\Http\Resources\ReservationFieldResource;
use App\Http\Resources\Small\MediaSmallResource;
use App\Http\Resources\Small\OfficeSmallResource;
use App\Models\Implementation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductResource extends \App\Http\Resources\ProductResource
{
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
     * @param Product $product
     * @param bool $forWebshop
     * @return array
     */
    protected function getProductData(Request $request, Product $product, bool $forWebshop): array
    {
        $data = parent::getProductData($request, $product, $forWebshop);

        return [
            ...$data,
            ...($forWebshop ? $this->productFeatures(collect($data['funds'])) : []),
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
