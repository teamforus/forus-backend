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
            'id' => $faq->id,
            'description' => $faq->description,
            ...$faq->translateColumns($faq->only([
                'title', 'description_html',
            ]))
        ];
    }
}
