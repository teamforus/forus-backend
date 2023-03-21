<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    public const LOAD = [];
    public const LOAD_COUNT = [];
    public const LOAD_MORPH = [];

    protected string $resource_type = 'default';

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return $append ? array_map(function($load) use ($append) {
            return "$append.$load";
        }, static::LOAD) : static::LOAD;
    }

    /**
     * @param Model $builder
     * @return Model
     */
    protected static function load_morph(Model $builder): Model
    {
        foreach (static::LOAD_MORPH as $morphKey => $morphRelations) {
            $builder->loadMorph($morphKey, $morphRelations);
        }

        return $builder;
    }

    /**
     * @param string|null $append
     * @return array
     */
    public static function load_count(?string $append = null): array
    {
        return $append ? array_map(function($load) use ($append) {
            return "$append.$load";
        }, static::LOAD_COUNT) : static::LOAD_COUNT;
    }

    /**
     * @param Model|Collection|array $resource
     * @param array $attributes
     * @return static
     */
    public static function create(mixed $resource, array $attributes = []): static
    {
        if ($resource instanceof Model) {
            $resource = static::load_morph($resource)->load(static::load())->loadCount(static::load_count());
        }

        return (new static($resource))->setAttributes($attributes);
    }

    /**
     * @param Builder|Relation $query
     * @param int|Request|null $request
     * @param array $attributes
     * @return AnonymousResourceCollection
     */
    public static function queryCollection(
        Relation|Builder $query,
        Request|int|null $request = null,
        array $attributes = []
    ): AnonymousResourceCollection {
        if (!$request || $request instanceof Request) {
            $request = ($request ?: request())->input('per_page');
        }

        static::withMorphCollection($query)
            ->with(static::load())
            ->withCount(static::load_count());

        $collection = self::collection($query->paginate(is_numeric($request) ? $request : null));
        $collection->collection->map(fn (self $resource) => $resource->setAttributes($attributes));

        return $collection;
    }

    /**
     * @param Relation|Builder $builder
     * @return Relation|Builder
     */
    protected static function withMorphCollection(Relation|Builder $builder): Relation|Builder
    {
        foreach (static::LOAD_MORPH as $morphKey => $morphRelations) {
            $builder->with($morphKey, fn (MorphTo $morphTo) => $morphTo->morphWith($morphRelations));
        }

        return $builder;
    }

    /**
     * @param $model
     * @param string|array $key
     * @return array
     */
    protected function timestamps($model, ...$key): array
    {
        return static::staticTimestamps($model, $key);
    }

    /**
     * @param $model
     * @param string|array $key
     * @return array
     */
    protected static function staticTimestamps($model, ...$key): array
    {
        if (is_array($key[0] ?? null) || count($key) > 1) {
            return array_reduce(is_array($key[0] ?? null) ? $key[0] : $key, function($prev, $key) use ($model) {
                return array_merge($prev, static::staticTimestamps($model, $key));
            }, []);
        }

        return self::makeTimestampsStatic([
            $key[0] => $model[$key[0]] ?? null,
        ]);
    }

    /**
     * @param array $dates
     * @param bool $dateOnly
     * @param string|null $format
     * @return array
     */
    protected static function makeTimestampsStatic(array $dates, bool $dateOnly = false, string $format = null): array
    {
        return array_reduce(array_keys($dates), function($out, $key) use ($dates, $format, $dateOnly) {
            $date = $dates[$key] instanceof Carbon ? $dates[$key] : null;

            return array_merge($out, [
                $key => $dateOnly ?
                    $date?->format('Y-m-d') :
                    $date?->format('Y-m-d H:i:s'),
                $key . '_locale' => $dateOnly ?
                    format_date_locale($date, $format ?: 'short_date_locale') :
                    format_datetime_locale($date, $format ?: 'short_date_time_locale'),
            ]);
        }, []);
    }

    /**
     * @param array $dates
     * @param bool $dateOnly
     * @param string|null $format
     * @return array
     */
    protected function makeTimestamps(array $dates, bool $dateOnly = false, string $format = null): array
    {
        return static::makeTimestampsStatic($dates, $dateOnly, $format);
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes = []): static
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }
}
