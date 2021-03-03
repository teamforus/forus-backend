<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ImplementationResource
 * @package App\Http\Resources
 */
class ImplementationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request): ?array
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        return array_merge($implementation->only([
            'id', 'key', 'name', 'url_webshop', 'informal_communication',
        ]), [
            'has_terms_page' => $this->hasTermsPage($implementation->page_terms_and_conditions),
        ]);
    }

    /**
     * @param ImplementationPage|null $terms
     * @return bool
     */
    protected function hasTermsPage(?ImplementationPage $terms): bool
    {
        return $terms && (bool) ($terms->external ? $terms->external_url: $terms->content);
    }
}
