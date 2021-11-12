<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    public const LOAD = [];
    public const LOAD_COUNT = [];

    /**
     * @return array
     */
    public static function load(): array
    {
        return static::LOAD;
    }

    /**
     * @return array
     */
    public static function load_count(): array
    {
        return static::LOAD_COUNT;
    }

    /**
     * @param mixed $resource
     * @return static
     */
    public static function create($resource): self
    {
        return new static($resource instanceof Model ? $resource
            ->load(static::load())
            ->loadCount(static::load_count()) : $resource);
    }

    /**
     * @param Builder|Relation $query
     * @param Request|null $request
     * @return AnonymousResourceCollection
     */
    public static function queryCollection(
        Builder $query,
        Request $request = null
    ): AnonymousResourceCollection {
        $request = $request ?: BaseFormRequest::createFromBase(request());

        return self::collection($query
            ->with(static::load())
            ->withCount(static::load_count())
            ->paginate($request->input('per_page')));
    }

    /**
     * @param $model
     * @param string|array $key
     * @return array
     */
    protected function timestamps($model, ...$key): array {
        if (is_array($key[0] ?? null) || count($key) > 1) {
            return array_reduce(is_array($key[0] ?? null) ? $key[0] : $key, function($prev, $key) use ($model) {
                return array_merge($prev, $this->timestamps($model, $key));
            }, []);
        }

        $datetime = $model[$key[0]] ?? null;

        return [
            $key[0] => $datetime instanceof Carbon ? $datetime->format('Y-m-d H:i:s') : null,
            $key[0] . '_locale' => $datetime instanceof Carbon ? format_datetime_locale($datetime) : null,
        ];
    }
}
