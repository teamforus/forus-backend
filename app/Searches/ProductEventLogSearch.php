<?php


namespace App\Searches;


use App\Models\Product;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class ProductEventLogSearch extends BaseSearch
{
    protected Product $product;
    protected array $permissions;

    /**
     * @param Product $product
     * @param array $filters
     */
    public function __construct(Product $product, array $filters)
    {
        parent::__construct($filters, EventLog::query());

        $this->product = $product;
        $this->permissions = Config::get('forus.event_permissions', []);
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $builder->whereRelation('identity.primary_email', 'email', 'LIKE', "%$q%");
        }

        $builder->where(function (Builder $builder) {
            $builder->where(fn (Builder $q) => $this->whereProductUpdateDigest($q));
            $builder->where(fn (Builder $q) => $this->whereEvents($q, Product::class));
        });

        if (empty($this->getFilter('loggable', []))) {
            return $builder->whereRaw('FALSE');
        }

        return $builder->orderByDesc('created_at');
    }

    /**
     * @param Builder $builder
     * @param string $morphClass
     * @return void
     */
    protected function whereEvents(Builder $builder, string $morphClass): void
    {
        /** @var Model $morphModel */
        $morphModel = new $morphClass;

        if (!in_array($morphModel->getMorphClass(), $this->getFilter('loggable', []))) {
            return;
        }

        $this->whereLoggableId($builder, $this->getFilter('loggable_id'));
        $this->whereLoggable($builder, $morphModel);
    }

    /**
     * @param Builder $builder
     * @param Model $morphModel
     * @return void
     */
    protected function whereLoggable(Builder $builder, Model $morphModel): void
    {
        $morphKey = $morphModel->getMorphClass();
        $builder->whereIn('event', Arr::get($this->permissions, "$morphKey.events", []));
        $builder->whereHasMorph('loggable', $morphModel::class);

        $builder->whereIn('loggable_id', fn (QBuilder $q) => $q->fromSub(
            $morphModel, $morphModel->getTable()
        ));
    }

    /**
     * @param Builder|QBuilder $builder
     * @param int|null $loggable_id
     * @return void
     */
    protected function whereLoggableId(Builder|QBuilder $builder, ?int $loggable_id = null): void
    {
        if ($loggable_id) {
            $builder->whereIn('loggable_id', (array) $loggable_id);
        }
    }

    /**
     * @param Builder $builder
     * @return void
     */
    function whereProductUpdateDigest(Builder $builder): void
    {
        if (!$this->hasFilter('loggable_id') || $this->hasFilter('loggable' !== 'product')) {
            return;
        }

        $builder->where(function (Builder $builder) {
            $builder->where('event', Product::EVENT_UPDATED_DIGEST);
        });
    }
}