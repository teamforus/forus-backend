<?php

namespace App\Http\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;

/**
 * @property Faq $resource
 */
class FaqResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $faq = $this->resource;

        return [
            ...$faq->only(['id', 'type', 'description']),
            ...$faq->translateColumns($faq->only([
                'title', 'subtitle', 'description_html',
            ])),
        ];
    }
}
