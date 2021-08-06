<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Exception;

/**
 * Class SearchResource
 * @property Model|Organization|Product|Fund $resource
 * @package App\Http\Resources
 */
class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \Throwable
     */
    public function toArray($request): array
    {
        /** @var Fund|Organization|Product $model */
        $model = $this->resource;

        switch ($this->resource->item_type ?? '') {
            case 'fund': $model = Fund::find($this->resource->id); break;
            case 'product': $model = Product::find($this->resource->id); break;
            case 'provider': $model = Organization::find($this->resource->id); break;
        }

        $hasPrice = $model instanceof Product && !is_null($this->resource->price);

        return array_merge($this->resource->only('id', 'item_type'), [
            'name' => e($model->name),
            'description_text' => $model->description_text,
            'price' => $hasPrice ? currency_format($model->price) : null,
            'price_locale' => $hasPrice ? $model->price_locale : null,
            'media' => new MediaCompactResource($model->logo ?? $model->photo ?? null),
            'resource' => $this->makeResource($model),
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function makeResource(Model $model): JsonResource
    {
        switch (get_class($model)) {
            case Fund::class: return new FundResource($model);
            case Product::class: return new ProductResource($model->load(ProductResource::load()));
            case Organization::class: return new ProviderResource($model->load(OrganizationResource::load()));
            default: throw new Exception('Unknown search type!');
        }
    }

    /**
     * @param Model|Fund $model
     * @return Fund
     */
    public function typeFund(Model $model): ?Fund {
        return Fund::find($model->id);
    }

    /**
     * @param Model|Organization $model
     * @return Organization
     */
    public function typeOrganization(Model $model): ?Organization {
        return Organization::find($model->id);
    }

    /**
     * @param Model|Product $model
     * @return Product
     */
    public function typeProduct(Model $model): ?Product {
        return Product::find($model->id);
    }
}
