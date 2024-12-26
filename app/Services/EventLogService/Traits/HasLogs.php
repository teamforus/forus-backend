<?php

namespace App\Services\EventLogService\Traits;

use App\Http\Requests\BaseFormRequest;
use App\Services\EventLogService\EventLogService;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait HasLogs
{
    /**
     * @param string $event
     * @param array $models
     * @param array $raw_meta
     * @param string|null $identity_address
     * @return EventLog|Model
     */
    public function log(
        string $event,
        array $models = [],
        array $raw_meta = [],
        ?string $identity_address = null
    ): EventLog|Model {
        $request = BaseFormRequest::createFrom(request());
        $logService = resolve(EventLogService::class);
        $identity_address = $identity_address ?: $request->auth_address();

        $meta = array_reduce(
            array_keys(array_filter($models, fn($model) => $model !== null)),
            fn($carry, $key) => array_merge($carry, $logService->modelToMeta($key, $models[$key])),
            []
        );

        $data = array_merge([
            'client_type' => $request->client_type(),
            'client_version' => $request->client_version(),
            'implementation_key' => $request->implementation_key(),
        ], $meta, $raw_meta);

        return $this->logs()->create(compact('data', 'event', 'identity_address'));
    }

    /**
     * @return MorphMany
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(EventLog::class, 'loggable');
    }
}