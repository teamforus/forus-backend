<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

/**
 * @property Model|Organization|Product|Fund $resource
 */
class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request$request
     * @throws Throwable
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Fund|Organization|Product $model */
        $model = $this->resource;

        switch ($this->resource->item_type ?? '') {
            case 'fund': $model = Fund::find($this->resource->id);
                break;
            case 'product': $model = Product::find($this->resource->id);
                break;
            case 'provider': $model = Organization::find($this->resource->id);
                break;
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
     * @throws Throwable
     */
    public function makeResource(Model $model): JsonResource
    {
        return match (get_class($model)) {
            Fund::class => new FundResource($model),
            Product::class => new ProductResource($model->load(ProductResource::load())),
            Organization::class => new ProviderResource($model->load(OrganizationResource::loadDeps())),
            default => throw new Exception('Unknown search type!'),
        };
    }

    /**
     * @param Model $model
     * @return Fund|null
     */
    public function typeFund(Model $model): ?Fund
    {
        return Fund::find($model->id);
    }

    /**
     * @param Model $model
     * @return Organization|null
     */
    public function typeOrganization(Model $model): ?Organization
    {
        return Organization::find($model->id);
    }

    /**
     * @param Model $model
     * @return Product|null
     */
    public function typeProduct(Model $model): ?Product
    {
        return Product::find($model->id);
    }
}
