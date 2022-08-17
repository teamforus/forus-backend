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
     * @return static
     */
    public static function create(mixed $resource): static
    {
        if ($resource instanceof Model) {
            $resource = static::load_morph($resource)->load(static::load())->loadCount(static::load_count());
        }

        return new static($resource);
    }

    /**
     * @param Builder|Relation $query
     * @param int|Request|null $request
     * @return AnonymousResourceCollection
     */
    public static function queryCollection(
        Relation|Builder $query,
        Request|int|null $request = null
    ): AnonymousResourceCollection {
        if (!$request || $request instanceof Request) {
            $request = ($request ?: request())->input('per_page');
        }

        static::withMorphCollection($query)
            ->with(static::load())
            ->withCount(static::load_count());

        return self::collection($query->paginate(is_numeric($request) ? $request : null));
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

        $datetime = $model[$key[0]] ?? null;

        return [
            $key[0] => $datetime instanceof Carbon ? $datetime->format('Y-m-d H:i:s') : null,
            $key[0] . '_locale' => $datetime instanceof Carbon ? format_datetime_locale($datetime) : null,
        ];
    }
}
