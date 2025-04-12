<?php

namespace App\Http\Resources;

use App\Services\MailDatabaseLoggerService\Models\EmailLog;

/**
 * @property EmailLog $resource
 */
class EmailLogResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $log = $this->resource;

        return [
            ...$log->only([
                'id', 'subject', 'to_name', 'to_address', 'from_name', 'from_address', 'content',
            ]),
            'type' => $log->getType(),
            'content' => $log->getContent(),
            ...$this->makeTimestamps($log->only([
                'created_at',
            ])),
        ];
    }
}
