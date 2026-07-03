<?php

namespace App\Http\Resources;

use App\Models\ProviderMessage;
use Illuminate\Http\Request;
use Throwable;

/**
 * @property-read ProviderMessage $resource
 */
class ProviderMessageResource extends BaseJsonResource
{
    public const array LOAD = [
        'employee.identity',
        'identity',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @throws Throwable
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only('id', 'type', 'message'),
            'type_locale' => __("provider-messages.type.{$this->resource->type}"),
            'message_html' => $this->resource->getMessageHtml(),
            'employee' => $this->resource->employee ? [
                'id' => $this->resource->employee->id,
                'email' => $this->resource->employee->identity->email,
                'identity_address' => $this->resource->employee->identity_address,
            ] : null,
            'identity' => $this->resource->identity->only('id', 'email'),
            ...$this->timestamps($this->resource, 'created_at'),
        ];
    }
}
